<?php

namespace Tests\Unit\Services;

use App\Constants\PageSizes;
use App\Constants\PublicationType;
use App\Models\Publication;
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
    public function it_groups_the_page_by_year_newest_first_with_undated_last(): void
    {
        $groups = $this->service->groupByYear(new Collection([
            Publication::factory()->make(['year' => 1990, 'title' => 'A']),
            Publication::factory()->make(['year' => null, 'title' => 'Undated']),
            Publication::factory()->make(['year' => 2005, 'title' => 'B']),
            Publication::factory()->make(['year' => 1990, 'title' => 'C']),
        ]));

        $this->assertSame([2005, 1990, null], array_column($groups, 'year'));
        $this->assertSame(['2005', '1990', 'Undated'], array_column($groups, 'label'));
        $this->assertSame(['A', 'C'], $groups[1]['items']->pluck('title')->all());
    }

    #[Test]
    public function it_builds_filter_options_whose_urls_keep_the_other_filter(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1978)->create();
        Publication::factory()->published()->type(PublicationType::Report)->year(2001)->create();
        Publication::factory()->draft()->type(PublicationType::Chapter)->year(2020)->create();

        $data = $this->service->data('book', 2001);

        $this->assertSame(PublicationType::Book, $data['active']['type']);
        $this->assertSame(2001, $data['active']['year']);
        $this->assertTrue($data['isFiltered']);
        $this->assertSame(0, $data['publications']->total());
        $this->assertSame([], $data['groups']);

        $this->assertSame(['book', 'report'], array_column($data['types'], 'value'));
        $this->assertSame([true, false], array_column($data['types'], 'active'));
        $this->assertSame(route('publications', ['type' => 'report', 'year' => 2001]), $data['types'][1]['url']);

        $this->assertSame(['2001', '1978'], array_column($data['years'], 'value'));
        $this->assertSame(route('publications', ['type' => 'book', 'year' => 1978]), $data['years'][1]['url']);

        $this->assertSame(route('publications'), $data['clearUrl']);
        $this->assertSame(route('publications', ['year' => 2001]), $data['allTypesUrl']);
    }

    #[Test]
    public function unknown_types_and_years_are_treated_as_no_filter_and_pages_hold_twenty(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1978)->count(PageSizes::PUBLICATIONS + 1)->create();

        $data = $this->service->data('nonsense', 1234);

        $this->assertNull($data['active']['type']);
        $this->assertNull($data['active']['year']);
        $this->assertFalse($data['isFiltered']);
        $this->assertSame(PageSizes::PUBLICATIONS, $data['publications']->perPage());
        $this->assertSame(PageSizes::PUBLICATIONS + 1, $data['publications']->total());
        $this->assertSame(PageSizes::PUBLICATIONS, $data['groups'][0]['items']->count());
        $this->assertSame('publications', $data['section']['key']);
    }
}
