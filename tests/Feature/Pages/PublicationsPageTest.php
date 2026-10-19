<?php

namespace Tests\Feature\Pages;

use App\Constants\Disks;
use App\Constants\PageSizes;
use App\Constants\PublicationType;
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
            ->assertSeeInOrder(['section-header', 'Publications', 'rule-brass', 'id="section-title"'], false)
            ->assertSee('Publications will be listed here as they are catalogued.')
            ->assertDontSee('Draft paper')
            ->assertDontSee('aria-label="Type"', false)
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function it_lists_publications_grouped_by_year_newest_first_with_every_populated_field(): void
    {
        $book = Publication::factory()->published()->type(PublicationType::Book)->year(1978)->create([
            'title' => 'Principles of Dryland Agronomy', 'authors' => 'Khidir, M. O.', 'venue_publisher' => 'Khartoum University Press',
            'doi' => '10.1000/abc', 'citation' => 'Khidir, M. O. (1978). Principles of Dryland Agronomy.', 'url' => 'https://www.example.org/book',
        ]);
        Publication::factory()->published()->type(PublicationType::JournalArticle)->year(1995)->create([
            'title' => 'Soil fertility trends', 'authors' => null, 'venue_publisher' => '', 'doi' => null, 'citation' => null, 'url' => null,
        ]);
        $this->app->make(PublicationService::class)->attachFile($book, UploadedFile::fake()->createWithContent('book.pdf', str_repeat('a', 2150 * 1024)));

        $response = $this->get(route('publications'))->assertOk();

        $response
            ->assertSeeInOrder([
                'Showing 1–2 of 2',
                'id="year-1995"', '1995', 'Journal article · 1995', 'Soil fertility trends',
                'id="year-1978"', '1978', 'Book · 1978', 'Principles of Dryland Agronomy', 'Khidir, M. O.', 'Khartoum University Press',
                'href="https://doi.org/10.1000/abc"', 'DOI · 10.1000/abc',
                'href="https://www.example.org/book"', 'Read online · example.org',
                'target="_blank"', 'Download · PDF · 2.1 MB',
                '<details class="cite', '<summary class="link-meta">Cite</summary>', 'Khidir, M. O. (1978). Principles of Dryland Agronomy.', 'Copy citation',
            ], false)
            ->assertDontSee('Publications will be listed here');

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, '<details class="cite'), 'Only publications with a citation get a Cite disclosure');
        $this->assertSame(1, substr_count($html, 'DOI · '), 'Only publications with a DOI get a DOI link');
        $this->assertStringNotContainsString('target="_blank" rel="noopener" class="link-meta">Read online', $html, 'External links open in the same tab');
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'data-rise-group'));
    }

    #[Test]
    public function type_and_year_filters_come_from_the_query_string_and_are_reflected_in_the_links(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1978)->create(['title' => 'Old book']);
        Publication::factory()->published()->type(PublicationType::Book)->year(2001)->create(['title' => 'New book']);
        Publication::factory()->published()->type(PublicationType::Report)->year(2001)->create(['title' => 'A report']);

        $this->get(route('publications', ['type' => 'book']))
            ->assertOk()
            ->assertSee('Old book')->assertSee('New book')->assertDontSee('A report')
            ->assertSee('Showing 1–2 of 2')
            ->assertSee('href="'.e(route('publications', ['type' => 'book'])).'" class="filter-pill" aria-current="true">Book', false)
            ->assertSee('href="'.e(route('publications', ['type' => 'report'])).'" class="filter-pill">Report', false)
            ->assertSee('<input type="hidden" name="type" value="book">', false)
            ->assertSee('href="'.route('publications').'" class="filter-pill">Clear filters', false);

        $this->get(route('publications', ['type' => 'book', 'year' => 2001]))
            ->assertOk()
            ->assertSee('New book')->assertDontSee('Old book')->assertDontSee('A report')
            ->assertSee('<option value="2001" selected>2001</option>', false)
            ->assertSee('href="'.e(route('publications', ['type' => 'report', 'year' => 2001])).'"', false);

        $this->get(route('publications', ['year' => 1978]))
            ->assertOk()
            ->assertSee('Old book')->assertDontSee('New book')
            ->assertSee('href="'.e(route('publications', ['year' => 1978])).'" class="filter-pill" aria-current="true">All types', false);
    }

    #[Test]
    public function unknown_filter_values_show_an_empty_result_or_are_ignored(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->year(1978)->create(['title' => 'Old book']);

        $this->get(route('publications', ['type' => 'not-a-type']))->assertOk()->assertSee('Old book');
        $this->get(route('publications', ['year' => 1900]))->assertOk()->assertSee('Old book');

        $this->get(route('publications', ['type' => 'report']))
            ->assertOk()
            ->assertSee('No publications match these filters')
            ->assertSee('Nothing has been published under this type or year yet.')
            ->assertDontSee('Old book');
    }

    #[Test]
    public function malformed_filters_redirect_to_the_unfiltered_list(): void
    {
        $this->get(route('publications', ['year' => 'abc']))->assertRedirect(route('publications'));
    }

    #[Test]
    public function it_paginates_at_twenty_and_keeps_filters_in_page_links(): void
    {
        Publication::factory()->published()->type(PublicationType::Book)->count(PageSizes::PUBLICATIONS + 1)->create();

        $response = $this->get(route('publications', ['type' => 'book']))->assertOk();

        $response
            ->assertSee('Showing 1–20 of 21')
            ->assertSee('aria-label="Pagination"', false)
            ->assertSee('Page 1 of 2')
            ->assertSee('rel="next"', false);

        $this->assertStringContainsString('type=book', $response->getContent());
        $this->assertMatchesRegularExpression('/href="[^"]*type=book[^"]*page=2[^"]*"|href="[^"]*page=2[^"]*type=book[^"]*"/', $response->getContent());

        $this->get(route('publications', ['type' => 'book', 'page' => 2]))->assertOk()->assertSee('Showing 21–21 of 21')->assertSee('Page 2 of 2');
    }

    #[Test]
    public function the_publications_section_is_marked_current_in_the_navigation(): void
    {
        $html = $this->get(route('publications'))->assertOk()->getContent();

        preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);

        $this->assertSame([route('publications')], array_values(array_unique($matches[1])));
    }
}
