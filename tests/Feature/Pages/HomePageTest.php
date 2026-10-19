<?php

namespace Tests\Feature\Pages;

use App\Constants\Disks;
use App\Constants\Sections;
use App\Constants\Taglines;
use App\Models\Award;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\Tribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function the_hero_shows_the_fixed_copy_both_buttons_and_the_scroll_scale_hooks(): void
    {
        $response = $this->get(route('home'))->assertOk();

        $response
            ->assertSee('<meta name="description" content="'.e(Taglines::HERO_LINE).'">', false)
            ->assertSeeInOrder(['glow-brass', 't-label text-brass', e(Taglines::HERO_LABEL), 't-display', 'Professor', 'Mohamed Osman Khidir', e(Taglines::HERO_LINE)], false)
            ->assertSee('min-h-[calc(100svh-5rem)]', false)
            ->assertSee('<a href="'.route('about').'" class="btn-pill-filled">'.e(Taglines::READ_STORY).'</a>', false)
            ->assertSee('<a href="'.route('tributes').'" class="btn-pill-outline">'.e(Taglines::LEAVE_TRIBUTE).'</a>', false)
            ->assertDontSee('livewire', false)
            ->assertDontSee('aria-current="page"', false);

        $this->assertSame(1, substr_count($response->getContent(), 'data-scroll-scale'), 'Without a portrait only the hero text recedes');
    }

    #[Test]
    public function the_hero_portrait_is_a_warm_monochrome_responsive_image_with_a_place_year_caption(): void
    {
        Profile::factory()->published()->withPortrait()->create();

        $html = $this->get(route('home'))->assertOk()->getContent();

        preg_match('/<section class="hero.*?<\/section>/s', $html, $hero);

        $this->assertSame(2, substr_count($hero[0], 'data-scroll-scale'));
        $this->assertStringContainsString('hero__portrait', $hero[0]);
        $this->assertStringContainsString('photo__frame overflow-hidden rounded-card', $hero[0]);
        $this->assertStringContainsString('aspect-ratio: 4 / 5', $hero[0]);
        $this->assertMatchesRegularExpression('/<img[^>]*srcset="[^"]*480w[^"]*960w[^"]*1600w"/', $hero[0]);
        $this->assertMatchesRegularExpression('/<img[^>]*class="photo-portrait h-full w-full object-cover"[^>]*loading="eager"/', $hero[0]);
        $this->assertStringContainsString('alt="Placeholder portrait of Professor Mohamed Osman Khidir"', $hero[0]);
        $this->assertStringContainsString('<span class="t-label text-brass">Khartoum, 1998</span>', $hero[0]);
    }

    #[Test]
    public function the_quote_band_sits_on_ivory_under_a_drawing_brass_rule(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['bg-ivory', 'rule-brass" data-rule', 't-quote', e(Taglines::HOME_QUOTE)], false);
    }

    #[Test]
    public function the_chapters_show_published_content_with_staggered_rises(): void
    {
        Profile::factory()->published()->create(['short_bio' => 'A short published biography.', 'title' => 'Professor of Agronomy', 'affiliation' => 'University of Khartoum']);
        Publication::factory()->published()->create(['title' => 'Newest paper', 'year' => 2012, 'venue_publisher' => 'Field Journal']);
        Publication::factory()->published()->create(['title' => 'Older paper', 'year' => 1998]);
        Publication::factory()->published()->create(['title' => 'Oldest paper', 'year' => 1980]);
        Publication::factory()->published()->create(['title' => 'Not shown', 'year' => 1970]);
        Publication::factory()->draft()->create(['title' => 'Draft paper', 'year' => 2020]);
        Award::factory()->published()->count(4)->create();
        Award::factory()->draft()->create();
        Tribute::factory()->approved()->count(3)->create(['message' => 'An approved tribute.']);
        Tribute::factory()->approved()->create(['message' => 'A fourth tribute that is not shown.', 'approved_at' => now()->subYears(5)]);
        Tribute::factory()->pending()->create(['message' => 'A pending tribute.']);

        $response = $this->get(route('home'))->assertOk();

        $response
            ->assertSeeInOrder(['Chapter one', 'Life', 'A short published biography.', 'Professor of Agronomy · University of Khartoum', 'href="'.route('about').'"'], false)
            ->assertSeeInOrder(['Chapter two', 'Work', 'Newest paper', 'Field Journal', 'Older paper', 'Oldest paper', 'href="'.route('publications').'"', '>4<', 'Awards and honours', 'href="'.route('cv').'"'], false)
            ->assertSeeInOrder(['Chapter three', 'Tributes', 'An approved tribute.', 'href="'.route('tributes').'"'], false)
            ->assertDontSee('Not shown')
            ->assertDontSee('Draft paper')
            ->assertDontSee('A fourth tribute')
            ->assertDontSee('A pending tribute');

        $this->assertSame(3, substr_count($response->getContent(), 'An approved tribute.'));
        $this->assertGreaterThanOrEqual(5, substr_count($response->getContent(), 'data-rise-group'));
        $this->assertSame(3, substr_count($response->getContent(), '<div class="section-header">'));
    }

    #[Test]
    public function each_chapter_has_a_designed_empty_state_when_nothing_is_published(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('The biography is being prepared.')
            ->assertSee('Publications will be listed here as they are catalogued.')
            ->assertSee('No tributes have been published yet. Yours could be the first.')
            ->assertDontSee('font-display text-7xl', false);
    }

    #[Test]
    public function the_section_row_links_videos_pictures_teaching_commentary_and_community_as_quiet_cards(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        preg_match_all('/<a href="([^"]+)" class="section-card card[^"]*">\s*<span class="t-label text-bronze">([^<]+)<\/span>/', $html, $cards, PREG_SET_ORDER);

        $this->assertSame(
            array_map(fn (string $key): string => route(Sections::definitions()[$key]['route']), Sections::homeRow()),
            array_column($cards, 1),
        );
        $this->assertSame(['Videos', 'Pictures', 'Teaching', 'Commentary', 'Community'], array_column($cards, 2));
    }
}
