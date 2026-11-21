<?php

namespace Tests\Feature\Pages;

use App\Constants\Disks;
use App\Constants\PageSizes;
use App\Constants\PublicationType;
use App\Constants\Typesetting;
use App\Models\Publication;
use App\Services\PublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicationsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function it_renders_an_empty_state_without_published_publications(): void
    {
        Publication::factory()->draft()->create(['title' => 'Draft paper']);

        $this->get(route('publications'))
            ->assertOk()
            ->assertSee('<title>Publications · ', false)
            ->assertSeeInOrder(['chapter-opening', 'Publications', 'rule-brass', 'id="section-title"'], false)
            ->assertSee('Publications will be listed here as they are catalogued.')
            ->assertDontSee('Draft paper')
            ->assertDontSee('catalogue__filters', false)
            ->assertDontSee('Selected works')
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function it_lists_catalogue_records_grouped_by_decade_with_counts_and_every_populated_field(): void
    {
        $book = Publication::factory()->published()->type(PublicationType::Book)->year(1978)->topic('Agronomy')->create([
            'title' => 'Principles of Dryland Agronomy', 'authors' => 'Khidir, M. O.; Smith, J.', 'venue_publisher' => 'Khartoum University Press',
            'doi' => '10.1000/abc', 'url' => 'https://www.example.org/book', 'slug' => 'principles',
        ]);
        Publication::factory()->published()->type(PublicationType::Report)->year(1971)->create(['title' => 'Early report', 'url' => null, 'doi' => null]);
        Publication::factory()->published()->type(PublicationType::JournalArticle)->year(1995)->topic(null)->create([
            'title' => 'Soil fertility trends', 'authors' => null, 'venue_publisher' => '', 'doi' => null, 'url' => null,
        ]);
        $this->app->make(PublicationService::class)->attachFile($book, UploadedFile::fake()->createWithContent('book.pdf', str_repeat('a', 2150 * 1024)));

        $response = $this->get(route('publications'))->assertOk();

        $response
            ->assertSeeInOrder([
                'class="catalogue mt-12 lg:mt-14" x-data="catalogueSheet"',
                'aria-controls="catalogue-filters"', 'Refine the catalogue',
                '<aside id="catalogue-filters" class="catalogue__filters"',
                '<legend id="type-label" class="t-label form-label">Type</legend>', 'name="type" value="" checked="checked"', 'All types',
                '<legend class="t-label form-label">Years</legend>', 'for="filter-from"', 'for="filter-to"',
                '<legend id="topic-label" class="t-label form-label">Topic</legend>', 'Agronomy',
                'Showing 1–3 of 3 records',
                'id="decade-1990"', '1990s', '1 record', 'rule-brass',
                'class="record"', 'class="record__year" aria-hidden="true">1995<', 'Journal article', 'Soil fertility trends',
                'id="decade-1970"', '1970s', '2 records',
                'id="publication-principles"', '>1978<', 'Book · Agronomy', 'class="record__title">Principles of Dryland Agronomy',
                // The typesetter binds the initials with a no-break space.
                'class="record__authors"><span class="smallcaps author-self">Khidir, M.'.Typesetting::NBSP.'O.</span>; Smith, J.</p>',
                '<i>Khartoum University Press</i>', 'class="record__venue-year">1978',
                'href="https://doi.org/10.1000/abc"', '<span class="record__doi-label">DOI</span> <span class="font-mono">10.1000/abc</span>',
                'href="https://www.example.org/book"', 'Read online · example.org',
                'target="_blank"', 'Download · PDF · 2.1 MB',
                'Early report',
            ], false)
            ->assertDontSee('Publications will be listed here');

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'record__doi-label'), 'Only publications with a DOI get a DOI link');
        $this->assertStringNotContainsString('class="cite', $html, 'There is no citation panel');
        $this->assertStringNotContainsString('citePanel', $html);
        $this->assertStringNotContainsString('BibTeX', $html);
        $this->assertSame(1, substr_count($html, 'class="record__links"'), 'Records without any link have no link list');
        $this->assertStringNotContainsString('target="_blank" rel="noopener" class="link-meta">Read online', $html, 'External links open in the same tab');
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'data-rise-group'));
    }

    #[Test]
    public function the_selected_works_strip_shows_featured_publications_on_the_unfiltered_first_page_only(): void
    {
        Publication::factory()->published()->featured()->type(PublicationType::Book)->year(1978)->create(['title' => 'Featured book', 'venue_publisher' => 'Khartoum University Press', 'sort_order' => 2]);
        Publication::factory()->published()->featured()->type(PublicationType::Report)->year(1990)->create(['title' => 'Featured report', 'sort_order' => 1]);
        Publication::factory()->draft()->featured()->create(['title' => 'Draft featured']);
        Publication::factory()->published()->type(PublicationType::Book)->count(PageSizes::PUBLICATIONS)->create();

        $this->get(route('publications'))
            ->assertOk()
            ->assertSeeInOrder([
                '<section class="selected-works mt-12 lg:mt-16" aria-labelledby="selected-works-title">', 'Selected works', 'rule-brass',
                'class="selected-works__item"', 'Report', 'Featured report', '>1990<',
                'class="selected-works__item"', 'Book', 'Featured book', 'Khartoum University Press', '>1978<',
                'class="catalogue mt-12 lg:mt-14"',
            ], false)
            ->assertDontSee('Draft featured');

        $this->get(route('publications', ['type' => 'book']))->assertOk()->assertDontSee('Selected works');
        $this->get(route('publications', ['page' => 2]))->assertOk()->assertDontSee('Selected works');
    }

    #[Test]
    public function type_year_range_and_topic_filters_come_from_the_query_string(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1978)->topic('Agronomy')->create(['title' => 'Old book']);
        Publication::factory()->published()->type(PublicationType::Book)->year(2001)->topic('Soil fertility')->create(['title' => 'New book']);
        Publication::factory()->published()->type(PublicationType::Report)->year(1990)->topic('Agronomy')->create(['title' => 'A report']);

        $this->get(route('publications', ['type' => 'book']))
            ->assertOk()
            ->assertSee('Old book')->assertSee('New book')->assertDontSee('A report')
            ->assertSee('Showing 1–2 of 2 records · Book')
            ->assertSee('name="type" value="book" checked', false)
            ->assertSee('<span class="catalogue__badge">1<span class="sr-only"> active</span></span>', false)
            ->assertSee('href="'.route('publications').'" class="filter-pill">Clear filters', false);

        $this->get(route('publications', ['from' => 1980, 'to' => 2005]))
            ->assertOk()
            ->assertSee('New book')->assertSee('A report')->assertDontSee('Old book')
            ->assertSee('Showing 1–2 of 2 records · 1980–2005');

        $this->get(route('publications', ['from' => 1990, 'to' => 2001]))
            ->assertOk()
            ->assertSeeInOrder(['id="filter-from"', '<option value="1990" selected>1990</option>', 'id="filter-to"', '<option value="2001" selected>2001</option>'], false);

        $this->get(route('publications', ['from' => 2005, 'to' => 1985]))
            ->assertOk()->assertSee('Showing 1–2 of 2 records · 1985–2005');

        $filtered = $this->get(route('publications', ['topic' => 'agronomy', 'type' => 'report']))->assertOk();
        preg_match('/<main.*<\/main>/s', $filtered->getContent(), $catalogue);
        $this->assertStringContainsString('A report', $catalogue[0]);
        $this->assertStringNotContainsString('Old book', $catalogue[0]);
        $this->assertStringNotContainsString('New book', $catalogue[0], 'Only the masthead may still feature the latest publication');
        $filtered
            ->assertSee('Showing 1–1 of 1 record · Report · Agronomy')
            ->assertSee('name="topic" value="Agronomy" checked', false)
            ->assertSee('<span class="catalogue__badge">2<span class="sr-only"> active</span></span>', false);

        // The single-year parameter of the earlier list still works as a one-year range.
        $byYear = $this->get(route('publications', ['year' => 1978]))->assertOk()->assertSee('Showing 1–1 of 1 record · 1978');
        preg_match('/<main.*<\/main>/s', $byYear->getContent(), $catalogue);
        $this->assertStringContainsString('Old book', $catalogue[0]);
        $this->assertStringNotContainsString('New book', $catalogue[0]);
    }

    #[Test]
    public function unknown_filter_values_show_an_empty_result_or_are_ignored(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1978)->topic('Agronomy')->create(['title' => 'Old book']);
        Publication::factory()->published()->type(PublicationType::Report)->year(1990)->topic('Agronomy')->create(['title' => 'A report']);

        $this->get(route('publications', ['type' => 'not-a-type']))->assertOk()->assertSee('Old book');
        $this->get(route('publications', ['type' => 'chapter']))->assertOk()->assertSee('Old book')->assertSee('A report');
        $this->get(route('publications', ['topic' => 'Unknown']))->assertOk()->assertSee('Old book');
        $this->get(route('publications', ['from' => 2050]))->assertOk()->assertSee('Old book');

        $this->get(route('publications', ['type' => 'report', 'to' => 1980]))
            ->assertOk()
            ->assertSee('No publications match these filters')
            ->assertSee('Nothing has been catalogued under these filters yet.')
            ->assertDontSee('Old book')
            ->assertDontSee('id="decade-', false);
    }

    #[Test]
    public function malformed_filters_redirect_to_the_unfiltered_list(): void
    {
        $this->get(route('publications', ['year' => 'abc']))->assertRedirect(route('publications'));
        $this->get(route('publications', ['from' => 'abc']))->assertRedirect(route('publications'));
        $this->get(route('publications', ['to' => '78']))->assertRedirect(route('publications'));
        $this->get(route('publications', ['topic' => str_repeat('a', 300)]))->assertRedirect(route('publications'));
    }

    #[Test]
    public function it_paginates_at_twenty_and_keeps_filters_in_page_links(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->count(PageSizes::PUBLICATIONS + 1)->create();

        $response = $this->get(route('publications', ['type' => 'book']))->assertOk();

        $response
            ->assertSee('Showing 1–20 of 21 records')
            ->assertSee('aria-label="Pagination"', false)
            ->assertSee('Page 1 of 2')
            ->assertSee('rel="next"', false);

        $this->assertStringContainsString('type=book', $response->getContent());
        $this->assertMatchesRegularExpression('/href="[^"]*type=book[^"]*page=2[^"]*"|href="[^"]*page=2[^"]*type=book[^"]*"/', $response->getContent());

        $this->get(route('publications', ['type' => 'book', 'page' => 2]))->assertOk()->assertSee('Showing 21–21 of 21 records')->assertSee('Page 2 of 2');
    }

    #[Test]
    public function the_publications_section_is_marked_current_in_the_navigation(): void
    {
        $html = $this->get(route('publications'))->assertOk()->getContent();

        preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);

        $this->assertSame([route('publications')], array_values(array_unique($matches[1])));
    }
}
