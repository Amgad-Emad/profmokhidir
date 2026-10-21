<?php

namespace Tests\Feature\Pages;

use App\Constants\PageSizes;
use App\Constants\SocialCards;
use App\Constants\Taglines;
use App\Constants\TributeRelationship;
use App\Models\Tribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributesPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_page_opens_on_evergreen_with_the_label_the_quote_heading_the_seal_and_a_share_card(): void
    {
        $response = $this->get(route('tributes'))->assertOk();

        $response
            ->assertSee('<title>Tributes · ', false)
            ->assertSeeInOrder([
                'glow-brass on-dark bg-evergreen', 't-label text-brass', Taglines::TRIBUTES_LABEL,
                'rule-brass', 'id="section-title" class="t-quote', Taglines::TRIBUTES_HEADING,
                'class="seal seal--open" aria-hidden="true" data-rise', 'brand-mark--ring', 'data-tone="ivory"', 'seal__rule',
                'href="'.route('tributes.create').'" class="btn-pill-filled">'.Taglines::LEAVE_TRIBUTE,
            ], false)
            ->assertSee('<meta property="og:image" content="'.asset(SocialCards::TRIBUTES).'">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta property="og:image:height" content="630">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function the_empty_state_invites_the_first_tribute(): void
    {
        Tribute::factory()->pending()->create(['message' => 'Still waiting for review']);
        Tribute::factory()->rejected()->create(['message' => 'Turned down']);

        $this->get(route('tributes'))
            ->assertOk()
            ->assertSee('No tributes have been published yet. Yours could be the first.')
            ->assertDontSee('Still waiting for review')
            ->assertDontSee('Turned down')
            ->assertDontSee('data-book', false)
            ->assertDontSee('data-featured', false);
    }

    #[Test]
    public function approved_tributes_are_set_as_pages_of_a_book_with_name_relationship_and_location_but_never_email(): void
    {
        Tribute::factory()->approved()->relationship(TributeRelationship::Student)->create([
            'name' => 'Ahmed El-Sheikh', 'location' => 'Khartoum, Sudan', 'message' => 'He taught me to look twice.', 'email' => 'ahmed@example.com', 'sort_order' => 1,
        ]);
        Tribute::factory()->approved()->relationship(TributeRelationship::Colleague)->create([
            'name' => 'Leila Mahmoud', 'location' => null, 'message' => 'A generous colleague.', 'email' => 'leila@example.com', 'sort_order' => 2,
        ]);
        Tribute::factory()->pending()->create(['name' => 'Pending Person', 'message' => 'Not yet reviewed']);

        $response = $this->get(route('tributes'))->assertOk();

        $response
            ->assertSeeInOrder([
                'class="folio folio--top"', Taglines::TRIBUTES_BOOK_LABEL,
                '<nav class="book-filters mt-6" aria-label="Filter by relationship">',
                'class="book-filter smallcaps" aria-current="true">All</a>', 'Students</a>', 'Colleagues</a>', 'Family</a>', 'Friends</a>',
                '<ol class="book mt-14" data-rise-group data-book>',
                '<li class="book__leaf">', '<article class="tribute-entry">', '<blockquote class="tribute-entry__text">', '<span class="hang-quote">&ldquo;</span>He taught me to look twice.&rdquo;</p>',
                '<h3 class="tribute-entry__author smallcaps">Ahmed El-Sheikh</h3>', 't-label text-bronze tribute-entry__meta">Student · Khartoum, Sudan',
                'role="separator" data-ornament="rule-dot"',
                '<li class="book__leaf">', 'A generous colleague.&rdquo;</p>', '<h3 class="tribute-entry__author smallcaps">Leila Mahmoud</h3>', 't-label text-bronze tribute-entry__meta">Colleague</p>',
                'folio folio--bottom',
            ], false)
            ->assertDontSee('Others</a>', false)
            ->assertDontSee('ahmed@example.com')
            ->assertDontSee('leila@example.com')
            ->assertDontSee('example.com')
            ->assertDontSee('Pending Person')
            ->assertDontSee('No tributes have been published yet');
    }

    #[Test]
    public function the_relationship_filters_are_plain_links_that_narrow_the_book(): void
    {
        Tribute::factory()->approved()->relationship(TributeRelationship::Student)->create(['name' => 'Zq Student']);
        Tribute::factory()->approved()->relationship(TributeRelationship::Friend)->create(['name' => 'Zq Friend']);

        $this->get(route('tributes', ['relationship' => 'friend']))
            ->assertOk()
            ->assertSee('href="'.route('tributes').'" class="book-filter smallcaps">All</a>', false)
            ->assertSee('href="'.route('tributes', ['relationship' => 'friend']).'" class="book-filter smallcaps" aria-current="true">Friends</a>', false)
            ->assertSee('Zq Friend')
            ->assertDontSee('Zq Student');

        $this->get(route('tributes', ['relationship' => 'family']))
            ->assertOk()
            ->assertSee('No tributes from family have been published yet.')
            ->assertDontSee('Zq Friend');
    }

    #[Test]
    public function the_featured_tribute_opens_the_book_set_large_in_the_quote_style(): void
    {
        $featured = Tribute::factory()->featured()->relationship(TributeRelationship::Student)->create([
            'name' => 'Ahmed El-Sheikh', 'location' => 'Khartoum, Sudan', 'message' => 'He never answered a question with an answer.', 'email' => 'ahmed@example.com',
        ]);
        Tribute::factory()->approved()->create(['name' => 'Zq Other', 'message' => 'A page of the book.']);

        $response = $this->get(route('tributes'))->assertOk();

        $response
            ->assertSeeInOrder([
                'data-featured', '<div class="frontispiece" data-rise-group>', 'class="t-label text-bronze">'.Taglines::TRIBUTES_FEATURED_LABEL,
                '<blockquote class="frontispiece__quote t-quote text-evergreen">', '<span class="hang-quote">&ldquo;</span>He never answered a question with an answer.&rdquo;',
                '<p class="frontispiece__author smallcaps">Ahmed El-Sheikh</p>', 't-label text-bronze frontispiece__meta">Student · Khartoum, Sudan',
                'href="'.route('tributes.card', $featured).'"', 'data-ornament="fleuron"',
                'data-book', 'Zq Other',
            ], false)
            ->assertDontSee('ahmed@example.com');

        // The frontispiece is not repeated among the pages.
        $this->assertSame(1, substr_count($response->getContent(), 'He never answered a question with an answer.'));

        // Under another relationship the frontispiece stays closed.
        $this->get(route('tributes', ['relationship' => 'colleague']))->assertOk()->assertDontSee('data-featured', false);
    }

    #[Test]
    public function the_book_paginates_at_twenty_four_with_roman_page_numbers_and_plain_links(): void
    {
        Tribute::factory()->approved()->count(PageSizes::TRIBUTES + 1)->create();

        $this->assertSame(24, PageSizes::TRIBUTES);

        $this->get(route('tributes'))
            ->assertOk()
            ->assertSee('<nav class="book-pagination" aria-label="Pages of the book">', false)
            ->assertSee('<span class="sr-only">Page 1 of 2</span>', false)
            ->assertSee('<span aria-hidden="true">Page I of II</span>', false)
            ->assertSee('href="'.route('tributes', ['page' => 2]).'" class="book-pagination__link book-pagination__link--next smallcaps" rel="next">Next page</a>', false);

        $this->get(route('tributes', ['page' => 2]))
            ->assertOk()
            ->assertSee('Page II of II')
            ->assertSee('rel="prev"', false)
            ->assertDontSee('data-featured', false);

        // The filter survives in the page links.
        Tribute::factory()->approved()->relationship(TributeRelationship::Family)->count(PageSizes::TRIBUTES + 1)->create();
        $this->get(route('tributes', ['relationship' => 'family']))
            ->assertOk()
            ->assertSee('href="'.str_replace('&', '&amp;', route('tributes', ['relationship' => 'family', 'page' => 2])).'"', false);
    }

    #[Test]
    public function the_tributes_section_is_marked_current_on_every_tribute_page(): void
    {
        foreach (['tributes', 'tributes.create', 'tributes.thanks'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            preg_match_all('/<a href="([^"]+)"(?![^>]*breadcrumbs__link)[^>]*aria-current="page"[^>]*>/', $html, $matches);

            $this->assertSame([route('tributes')], array_values(array_unique($matches[1])), $route);
        }
    }

    #[Test]
    public function the_share_card_exists_at_the_published_size(): void
    {
        $path = public_path(SocialCards::TRIBUTES);

        $this->assertFileExists($path);

        $size = getimagesize($path);

        $this->assertSame([SocialCards::WIDTH, SocialCards::HEIGHT], [$size[0], $size[1]]);
        $this->assertSame('image/png', $size['mime']);
    }
}
