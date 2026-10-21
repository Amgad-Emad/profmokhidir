<?php

namespace Tests\Unit\Services;

use App\Constants\Catalogue;
use App\Constants\CitationStyle;
use App\Constants\PageSizes;
use App\Constants\PublicationType;
use App\Constants\Sections;
use App\Constants\Typesetting;
use App\Models\Publication;
use App\Services\NavigationService;
use App\Services\PublicationsPageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicationsPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private PublicationsPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PublicationsPageService::class);
    }

    #[Test]
    public function it_groups_the_page_by_decade_newest_first_with_undated_last_and_full_counts(): void
    {
        $groups = $this->service->groupByDecade(new Collection([
            Publication::factory()->make(['year' => 1990, 'title' => 'A']),
            Publication::factory()->make(['year' => null, 'title' => 'Undated']),
            Publication::factory()->make(['year' => 2005, 'title' => 'B']),
            Publication::factory()->make(['year' => 1999, 'title' => 'C']),
        ]), [2000 => 4, 1990 => 2, '' => 1]);

        $this->assertSame([2000, 1990, null], array_column($groups, 'decade'));
        $this->assertSame(['2000s', '1990s', Catalogue::UNDATED_LABEL], array_column($groups, 'label'));
        $this->assertSame(['decade-2000', 'decade-1990', 'decade-undated'], array_column($groups, 'anchor'));
        $this->assertSame([4, 2, 1], array_column($groups, 'count'), 'Counts cover the whole filtered catalogue, not just this page');
        $this->assertSame(['A', 'C'], $groups[1]['items']->pluck('title')->all());

        $fallback = $this->service->groupByDecade(new Collection([Publication::factory()->make(['year' => 1978])]));
        $this->assertSame(1, $fallback[0]['count']);
    }

    #[Test]
    public function it_builds_the_filter_options_and_active_filters(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1978)->topic('Agronomy')->create();
        Publication::factory()->published()->type(PublicationType::Report)->year(2001)->topic('Soil fertility')->create();
        Publication::factory()->draft()->type(PublicationType::Chapter)->year(2020)->topic('Draft topic')->create();

        $data = $this->service->data('book', 1970, 2001, 'agronomy');

        $this->assertSame(PublicationType::Book, $data['active']['type']);
        $this->assertSame(1970, $data['active']['from']);
        $this->assertSame(2001, $data['active']['to']);
        $this->assertSame('Agronomy', $data['active']['topic'], 'Topics match without regard to case');
        $this->assertSame(3, $data['activeCount']);
        $this->assertSame(['Book', '1970–2001', 'Agronomy'], $data['activeSummary']);
        $this->assertTrue($data['isFiltered']);
        $this->assertSame(1, $data['publications']->total());

        $this->assertSame(['book', 'report'], array_column($data['types'], 'value'));
        $this->assertSame([true, false], array_column($data['types'], 'active'));
        $this->assertSame([2001, 1978], $data['years']);
        $this->assertSame(['Agronomy', 'Soil fertility'], $data['topics']);
        $this->assertSame(route('publications'), $data['clearUrl']);
        $this->assertSame($this->app->make(NavigationService::class)->chapter(Sections::PUBLICATIONS), $data['chapter']);
    }

    #[Test]
    public function unknown_types_topics_and_out_of_range_years_are_treated_as_no_filter_and_pages_hold_twenty(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1978)->count(PageSizes::PUBLICATIONS + 1)->create();

        $data = $this->service->data('nonsense', 3000, null, 'Unknown topic');

        $this->assertNull($data['active']['type']);
        $this->assertNull($data['active']['from']);
        $this->assertNull($data['active']['topic']);
        $this->assertFalse($data['isFiltered']);
        $this->assertSame(0, $data['activeCount']);
        $this->assertSame(PageSizes::PUBLICATIONS, $data['publications']->perPage());
        $this->assertSame(PageSizes::PUBLICATIONS + 1, $data['publications']->total());
        $this->assertSame(PageSizes::PUBLICATIONS, $data['groups'][0]['items']->count());
        $this->assertSame(PageSizes::PUBLICATIONS + 1, $data['groups'][0]['count']);
        $this->assertSame('publications', $data['section']['key']);

        // A type that exists as a case but has nothing published is no filter either.
        $this->assertNull($this->service->data('chapter')['active']['type']);
    }

    #[Test]
    public function a_reversed_year_range_is_put_the_right_way_round_and_open_ends_are_described(): void
    {
        $options = ['years' => [2001, 1978], 'types' => [PublicationType::Book], 'topics' => []];

        $filters = $this->service->normalise($options, null, 2001, 1978, null);
        $this->assertSame([1978, 2001], [$filters->from, $filters->to]);

        Publication::factory()->published()->year(1978)->create();
        Publication::factory()->published()->year(2001)->create();

        $this->assertSame(['From 1990'], $this->service->data(null, 1990)['activeSummary']);
        $this->assertSame(['Until 1990'], $this->service->data(null, null, 1990)['activeSummary']);
        $this->assertSame(['1978'], $this->service->data(null, 1978, 1978)['activeSummary']);
    }

    #[Test]
    public function the_featured_strip_appears_only_on_the_unfiltered_first_page(): void
    {
        Publication::factory()->published()->featured()->create(['title' => 'Featured', 'year' => 1990]);
        Publication::factory()->published()->create(['title' => 'Plain', 'year' => 1990]);

        $this->assertSame(['Featured'], $this->service->data()['featured']->pluck('title')->all());
        $this->assertTrue($this->service->data(null, 1990)['featured']->isEmpty());
    }

    #[Test]
    public function each_record_on_the_page_has_its_author_line_and_every_citation_style(): void
    {
        $publication = Publication::factory()->published()->create(['authors' => 'Smith, J. and M. O. Khidir', 'year' => 1990]);

        $data = $this->service->data();
        $id = (int) $publication->getKey();

        $this->assertSame(CitationStyle::cases(), array_column($data['citations'][$id], 'style'));
        $this->assertSame(
            'Smith, J.; <span class="smallcaps author-self">M.'.Typesetting::NBSP.'O.'.Typesetting::NBSP.'Khidir</span>',
            (string) $data['authors'][$id],
        );
    }

    #[Test]
    public function the_author_line_escapes_names_and_is_empty_without_authors(): void
    {
        $this->assertSame('', (string) $this->service->authorsHtml(null));
        $this->assertSame('', (string) $this->service->authorsHtml('  '));
        $this->assertSame('&lt;b&gt;Smith&lt;/b&gt;, J.', (string) $this->service->authorsHtml('<b>Smith</b>, J.'));
        $this->assertSame('<span class="smallcaps author-self"><span class="caps">KHIDIR</span>, M.</span>', (string) $this->service->authorsHtml('KHIDIR, M.'));
    }
}
