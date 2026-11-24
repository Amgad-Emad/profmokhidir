<?php

namespace Tests\Feature\Pages;

use App\Constants\CvSection;
use App\Constants\Disks;
use App\Constants\HomeTimeline;
use App\Constants\MediaProperties;
use App\Constants\PageDefinitions\HomeBlocks;
use App\Constants\Pages;
use App\Constants\Taglines;
use App\Models\Album;
use App\Models\Award;
use App\Models\CvEntry;
use App\Models\HomeQuote;
use App\Models\Profile;
use App\Models\Publication;
use App\Services\AlbumService;
use App\Services\HomeSectionsImportService;
use App\Services\PageService;
use App\Support\PlaceholderImage;
use App\View\Components\Site\Footer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
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
    public function the_hero_keeps_the_brand_layout_adds_the_epigraph_and_a_scroll_cue(): void
    {
        // A chapter for the scroll cue to point to: it shows only when the chapters do.
        Award::factory()->published()->create(['year' => 1985]);

        $response = $this->home()->assertOk();

        $response
            ->assertSee('<meta name="description" content="'.e(Taglines::HERO_LINE).'">', false)
            ->assertSeeInOrder(['glow-brass', 't-label text-accent-text', e(Taglines::HERO_LABEL), 't-display', 'Professor', 'Mohamed Osman Khidir', e(Taglines::HERO_LINE), 'epigraph text-ink hero__epigraph', 'Scholarship is the one inheritance', 'btn btn--primary', 'btn btn--secondary'], false)
            ->assertSee('<section class="hero glow-brass on-dark bg-surface text-ink" aria-labelledby="hero-title" data-masthead-hero>', false)
            ->assertSee('class="hero__inner relative', false)
            ->assertSee('<a href="'.route('about').'" class="btn btn--primary">'.e(Taglines::READ_STORY).'</a>', false)
            ->assertSee('<a href="'.route('tributes.create').'" class="btn btn--secondary">'.e(Taglines::LEAVE_TRIBUTE).'</a>', false)
            ->assertSee('<a href="#'.HomeTimeline::ANCHOR.'" class="scroll-cue t-label text-accent-text">', false)
            ->assertSee('<span class="sr-only"> to '.Taglines::HOME_CHAPTERS.'</span>', false)
            ->assertSee('<span class="scroll-cue__line" aria-hidden="true" data-rule></span>', false)
            ->assertSee('id="'.HomeTimeline::ANCHOR.'"', false)
            ->assertDontSee('livewire', false)
            ->assertDontSee('aria-current="page"', false);

        $this->assertSame(1, substr_count($response->getContent(), 'data-scroll-scale'), 'Without a portrait only the hero text recedes');
    }

    #[Test]
    public function the_hero_image_is_a_responsive_image_with_a_place_year_caption(): void
    {
        $pages = app(PageService::class);
        $pages->install();
        $page = $pages->findByKey(Pages::HOME);
        $media = $pages->uploadImage($page, 'hero_image', UploadedFile::fake()->image('portrait.jpg', 800, 1000));
        $pages->publishBlocks($page, ['hero_image' => [
            'media_id' => $media->getKey(), 'alt_text' => 'Portrait of Professor Khidir', 'caption' => 'Portrait',
            'place' => 'Khartoum', 'year' => 1998,
        ]]);

        $html = $this->home()->assertOk()->getContent();

        preg_match('/<section class="hero.*?<\/section>/s', $html, $hero);

        $this->assertSame(2, substr_count($hero[0], 'data-scroll-scale'));
        $this->assertStringContainsString('hero__portrait', $hero[0]);
        $this->assertStringContainsString('aspect-ratio: 4 / 5', $hero[0]);
        $this->assertMatchesRegularExpression('/<img[^>]*srcset="[^"]*480w[^"]*960w[^"]*1600w"/', $hero[0]);
        $this->assertMatchesRegularExpression('/<img[^>]*class="photo-portrait h-full w-full object-cover"[^>]*loading="eager"/', $hero[0]);
        $this->assertStringContainsString('<figcaption class="museum-label museum-label--ivory">', $hero[0]);
        $this->assertStringContainsString('<span class="museum-label__meta smallcaps">Khartoum, 1998</span>', $hero[0]);
    }

    #[Test]
    public function the_profile_portrait_does_not_stand_in_for_an_empty_hero_image(): void
    {
        Profile::factory()->published()->withPortrait()->create();

        $html = $this->home()->assertOk()->getContent();

        preg_match('/<section class="hero.*?<\/section>/s', $html, $hero);

        $this->assertStringNotContainsString('hero__portrait', $hero[0]);
        $this->assertSame(1, substr_count($hero[0], 'data-scroll-scale'));
    }

    #[Test]
    public function a_life_in_chapters_shows_his_honours_by_decade_with_photos_and_a_scroll_drawn_line(): void
    {
        CvEntry::factory()->published()->create(['section' => CvSection::Education, 'title' => 'B.Sc. (Agriculture)', 'organisation' => 'University of Khartoum', 'start_year' => 1958, 'end_year' => 1962]);
        Award::factory()->published()->create(['name' => 'Fellowship of the Academy', 'organisation' => 'Academy of Sciences', 'year' => 1967]);
        Award::factory()->draft()->create(['name' => 'Draft award', 'year' => 1970]);
        Award::factory()->published()->create(['name' => 'Order of Merit', 'organisation' => 'Republic of Sudan', 'year' => 1986]);
        $this->archivePhoto(1967, 'Students at Shambat');

        $html = $this->home()->assertOk()
            ->assertSeeInOrder([
                '1960–1969', '1967', 'Fellowship of the Academy', 'Award', 'Academy of Sciences', 'Students at Shambat', 'Shambat, 1967',
                '1980–1989', '1986', 'Order of Merit', 'Award', 'Republic of Sudan',
            ], false)
            // Numerals show only when an editor enters one: none is made up.
            ->assertDontSee('<p class="t-numeral text-accent-text">', false)
            ->assertDontSee('Draft award')
            ->assertDontSee('id="chapter-1950s"', false)
            ->getContent();

        $this->assertSame(2, substr_count($html, '<article class="life-chapter"'));
        $this->assertSame(2, substr_count($html, 'data-scroll-line'));
        $this->assertStringContainsString('<h3 id="chapter-1960s" class="life-chapter__years">1960–1969</h3>', $html);
        $this->assertMatchesRegularExpression('/life-chapter__photo" data-rise>\s*<figure[^>]*>.*?class="photo-archive h-full w-full object-cover"[^>]*loading="lazy"/s', $html);
    }

    #[Test]
    public function published_home_quotes_sit_between_chapters_as_evergreen_interludes(): void
    {
        foreach ([1965, 1975, 1985] as $year) {
            Award::factory()->published()->create(['year' => $year]);
        }

        HomeQuote::factory()->published()->sortOrder(1)->create(['quote' => 'Knowledge is the light we pass on.', 'attribution' => 'A former student', 'source' => 'Tribute']);
        HomeQuote::factory()->published()->sortOrder(2)->create(['quote' => 'A second line.', 'attribution' => null, 'source' => null]);
        HomeQuote::factory()->draft()->sortOrder(0)->create(['quote' => 'A draft quote.']);

        $html = $this->home()->assertOk()
            ->assertSeeInOrder(['id="chapter-1960s"', 'interlude glow-brass on-dark bg-surface text-ink', 't-quote pull-quote', 'Knowledge is the light we pass on.', 'A former student, Tribute', 'id="chapter-1970s"', 'A second line.', 'id="chapter-1980s"'], false)
            ->assertDontSee('A draft quote.')
            ->getContent();

        $this->assertSame(2, substr_count($html, '<figure class="interlude'));
        $this->assertSame(1, substr_count($html, 'interlude__credit'), 'A quote without attribution or source has no caption');
    }

    #[Test]
    public function his_work_in_numbers_shows_static_lining_figures_with_small_caps_labels(): void
    {
        Publication::factory()->published()->count(12)->create();
        Publication::factory()->draft()->create();
        Award::factory()->published()->count(4)->create(['year' => null]);
        CvEntry::factory()->published()->create(['section' => CvSection::Appointment, 'start_year' => 1969, 'end_year' => 2005]);
        Profile::factory()->published()->create(['students_supervised' => 48]);

        $html = $this->home()->assertOk()
            ->assertSeeInOrder([e(Taglines::HOME_NUMBERS), 'Publications', '>12<', 'Years of teaching', '>36<', 'Awards and honours', '>4<', 'Students supervised', '>48<'], false)
            ->getContent();

        preg_match('/<dl class="figures" data-rise-group>.*?<\/dl>/s', $html, $figures);
        $this->assertSame(4, substr_count($figures[0], '<dd class="figures-lining figure__value">'));
        $this->assertSame(4, substr_count($figures[0], '<dt class="smallcaps figure__label">'));
        $this->assertStringNotContainsString('data-count', $figures[0]);
    }

    #[Test]
    public function students_supervised_is_left_out_until_it_is_supplied(): void
    {
        Publication::factory()->published()->create();
        Profile::factory()->published()->create(['students_supervised' => null]);

        $this->home()->assertOk()->assertSee('Publication')->assertDontSee('Students supervised');
    }

    #[Test]
    public function sections_with_nothing_entered_are_left_out_with_no_placeholder(): void
    {
        $this->home()
            ->assertOk()
            ->assertDontSee('The chapters of his life will appear here as his record is catalogued.')
            ->assertDontSee('The figures will appear once his publications, appointments and honours are catalogued.')
            ->assertDontSee('class="empty-state', false)
            ->assertDontSee('<section class="life', false)
            ->assertDontSee('work-in-numbers', false)
            ->assertDontSee('class="scroll-cue', false)
            ->assertDontSee('<dl class="figures"', false)
            ->assertDontSee('<figure class="interlude', false);
    }

    #[Test]
    public function continue_the_story_links_about_publications_and_tributes_then_the_page_closes_with_the_seal(): void
    {
        $this->archivePhoto(1972, 'Field station');

        $html = $this->home()->assertOk()
            ->assertSeeInOrder([
                e(Taglines::HOME_CONTINUE),
                'href="'.route('about').'" class="section-card"', '>About Prof. Mokhidir</span>', e(Taglines::STORY_ABOUT),
                'href="'.route('publications').'" class="section-card"', '>Publications</span>', e(Taglines::STORY_PUBLICATIONS),
                'href="'.route('tributes').'" class="section-card"', '>Tributes</span>', e(Taglines::STORY_TRIBUTES),
                'class="seal"', e(Taglines::HOME_CLOSING),
            ], false)
            ->getContent();

        $this->assertSame(1, substr_count($html, 'section-card__strip'), 'Only one archive photo exists');
        $this->assertSame(3, substr_count($html, 'data-section-card='));
    }

    #[Test]
    public function the_footer_does_not_repeat_the_closing_line_on_the_home_page(): void
    {
        $html = $this->home()->assertOk()->getContent();

        preg_match('/<footer.*<\/footer>/s', $html, $footer);

        $this->assertStringContainsString('<p class="t-quote max-w-xl">'.Footer::HOME_CLOSING_LINE.'</p>', $footer[0]);
        $this->assertSame(1, substr_count($html, Taglines::HOME_CLOSING));
    }

    #[Test]
    public function headings_descend_in_order_and_everything_is_present_without_scripts(): void
    {
        CvEntry::factory()->published()->create(['start_year' => 1970]);

        $html = $this->home()->assertOk()->getContent();

        preg_match_all('/<h([1-6])\b/', $html, $levels);
        $this->assertSame('1', $levels[1][0]);
        $previous = 1;

        foreach ($levels[1] as $level) {
            $this->assertLessThanOrEqual($previous + 1, (int) $level, 'No heading level is skipped');
            $previous = (int) $level;
        }

        // Hidden start states live only in CSS gated on (scripting: enabled); the markup hides nothing.
        $this->assertStringNotContainsString('hidden', preg_replace('/sr-only|aria-hidden="true"|x-cloak/', '', strip_tags($html, '<main>')));
    }

    private function archivePhoto(int $year, string $caption): void
    {
        $this->app->make(AlbumService::class)->addPhoto(Album::factory()->published()->create(), PlaceholderImage::grey(64, 48), [
            MediaProperties::ALT_TEXT => $caption, MediaProperties::CAPTION => $caption,
            MediaProperties::PLACE => 'Shambat', MediaProperties::YEAR => $year,
        ]);
    }

    /**
     * The home page after its chapters, figures and cards are filled from the
     * records the test created (as the migration did when they moved into
     * Admin › Pages › Home).
     */
    private function home(): TestResponse
    {
        // Empty first: the migration already filled them when the test database was built, before this test's records existed.
        $pages = app(PageService::class);
        $pages->install();
        $pages->publishBlocks($pages->findByKey(Pages::HOME), [HomeBlocks::CHAPTER_ITEMS => [], HomeBlocks::FIGURES => [], HomeBlocks::STORY_CARDS => []]);
        app(HomeSectionsImportService::class)->import();

        return $this->get(route('home'));
    }
}
