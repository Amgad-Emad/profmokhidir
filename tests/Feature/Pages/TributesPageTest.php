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
    public function the_page_opens_on_evergreen_with_the_label_the_quote_heading_and_a_share_card(): void
    {
        $response = $this->get(route('tributes'))->assertOk();

        $response
            ->assertSee('<title>Tributes · ', false)
            ->assertSeeInOrder([
                'glow-brass on-dark bg-evergreen', 't-label text-brass', Taglines::TRIBUTES_LABEL,
                'rule-brass', 'id="section-title" class="t-quote', Taglines::TRIBUTES_HEADING,
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
            ->assertDontSee('tribute-wall', false);
    }

    #[Test]
    public function approved_tributes_appear_as_cards_with_message_name_relationship_and_location_but_never_email(): void
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
                'tribute-wall columns-1 gap-6 md:columns-2" data-rise-group',
                'break-inside-avoid', 'card card-ivory', 'font-display', 'italic', '&ldquo;He taught me to look twice.&rdquo;',
                'Ahmed El-Sheikh', 't-label text-bronze">Student · Khartoum, Sudan',
                '&ldquo;A generous colleague.&rdquo;', 'Leila Mahmoud', 't-label text-bronze">Colleague</p>',
            ], false)
            ->assertDontSee('ahmed@example.com')
            ->assertDontSee('leila@example.com')
            ->assertDontSee('example.com')
            ->assertDontSee('Pending Person')
            ->assertDontSee('No tributes have been published yet');
    }

    #[Test]
    public function the_wall_paginates_at_twenty_four(): void
    {
        Tribute::factory()->approved()->count(PageSizes::TRIBUTES + 1)->create();

        $this->assertSame(24, PageSizes::TRIBUTES);

        $this->get(route('tributes'))
            ->assertOk()
            ->assertSee('Page 1 of 2')
            ->assertSee('rel="next"', false);

        $this->get(route('tributes', ['page' => 2]))
            ->assertOk()
            ->assertSee('Page 2 of 2');
    }

    #[Test]
    public function the_tributes_section_is_marked_current_on_every_tribute_page(): void
    {
        foreach (['tributes', 'tributes.create', 'tributes.thanks'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();

            preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);

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
