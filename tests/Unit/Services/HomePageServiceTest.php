<?php

namespace Tests\Unit\Services;

use App\Constants\CacheKeys;
use App\Constants\CvSection;
use App\Constants\Disks;
use App\Constants\HomeTimeline;
use App\Constants\MediaProperties;
use App\Constants\PageDefinitions\HomeBlocks;
use App\Constants\Sections;
use App\Constants\Taglines;
use App\Models\Album;
use App\Models\Award;
use App\Models\CvEntry;
use App\Models\HomeQuote;
use App\Models\Profile;
use App\Models\Publication;
use App\Services\AlbumService;
use App\Services\HomePageService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomePageServiceTest extends TestCase
{
    use RefreshDatabase;

    private HomePageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(HomePageService::class);
    }

    #[Test]
    public function it_returns_empty_parts_and_the_fixed_copy_when_nothing_is_published(): void
    {
        Profile::factory()->draft()->create(['students_supervised' => 40]);
        Publication::factory()->draft()->count(2)->create();
        Award::factory()->draft()->create(['year' => 1990]);
        HomeQuote::factory()->draft()->create();

        $data = $this->service->data();

        $this->assertNull($data['profile']);
        $this->assertNull($data['portrait']);
        $this->assertSame([], $data['chapters']);
        $this->assertSame([], $data['stats']);
        $this->assertSame(HomeTimeline::ANCHOR, $data['anchor']);
        // No page copy stored: the built-in wording (App\Constants\PageDefinitions\HomeBlocks).
        $copy = $data['copy'];
        $this->assertSame((string) $copy['chaptersIntro'], HomeBlocks::CHAPTERS_INTRO);
        unset($copy['chaptersIntro']);
        $this->assertSame([
            'label' => Taglines::HERO_LABEL,
            'headingFirst' => HomeBlocks::HERO_HEADING_FIRST,
            'headingSecond' => HomeBlocks::HERO_HEADING_SECOND,
            'line' => Taglines::HERO_LINE,
            'epigraph' => Taglines::HOME_EPIGRAPH,
            'scroll' => Taglines::SCROLL_CUE,
            'chapters' => Taglines::HOME_CHAPTERS,
            'chaptersEmpty' => HomeBlocks::CHAPTERS_EMPTY,
            'numbers' => Taglines::HOME_NUMBERS,
            'numbersEmpty' => HomeBlocks::NUMBERS_EMPTY,
            'continue' => Taglines::HOME_CONTINUE,
            'closing' => Taglines::HOME_CLOSING,
            'readStory' => ['label' => Taglines::READ_STORY, 'url' => route('about')],
            'leaveTribute' => ['label' => Taglines::LEAVE_TRIBUTE, 'url' => route('tributes.create')],
        ], $copy);
    }

    #[Test]
    public function each_chapter_but_the_last_is_followed_by_the_next_published_quote(): void
    {
        foreach ([1965, 1975, 1985] as $year) {
            Award::factory()->published()->create(['year' => $year]);
        }

        HomeQuote::factory()->published()->sortOrder(2)->create(['quote' => 'Second']);
        HomeQuote::factory()->published()->sortOrder(1)->create(['quote' => 'First']);
        HomeQuote::factory()->published()->sortOrder(3)->create(['quote' => 'Third, never shown']);
        HomeQuote::factory()->draft()->sortOrder(0)->create(['quote' => 'Draft']);

        $chapters = $this->service->data()['chapters'];

        $this->assertSame(['First', 'Second', null], array_map(fn (array $chapter): ?string => $chapter['interlude']?->quote, $chapters));
    }

    #[Test]
    public function fewer_quotes_than_gaps_leave_the_later_gaps_empty(): void
    {
        foreach ([1965, 1975, 1985] as $year) {
            Award::factory()->published()->create(['year' => $year]);
        }

        HomeQuote::factory()->published()->create(['quote' => 'Only one']);

        $this->assertSame(['Only one', null, null], array_map(fn (array $chapter): ?string => $chapter['interlude']?->quote, $this->service->chapters()));
    }

    #[Test]
    public function the_figures_show_only_known_positive_numbers_in_order(): void
    {
        Publication::factory()->published()->count(3)->create();
        Award::factory()->published()->create();
        CvEntry::factory()->published()->create(['section' => CvSection::Appointment, 'start_year' => 1969, 'end_year' => 2005]);

        $stats = $this->service->stats(null);

        $this->assertSame([
            ['key' => HomeTimeline::STAT_PUBLICATIONS, 'value' => 3, 'label' => 'Publications'],
            ['key' => HomeTimeline::STAT_YEARS_TEACHING, 'value' => 36, 'label' => 'Years of teaching'],
            ['key' => HomeTimeline::STAT_AWARDS, 'value' => 1, 'label' => 'Award and honour'],
        ], $stats);

        $withStudents = $this->service->stats(Profile::factory()->published()->make(['students_supervised' => 48]));

        $this->assertSame(['key' => HomeTimeline::STAT_STUDENTS, 'value' => 48, 'label' => 'Students supervised'], end($withStudents));
    }

    #[Test]
    public function the_story_cards_lead_to_about_publications_and_tributes_with_unshown_photos_first(): void
    {
        $album = Album::factory()->published()->create();
        $photos = [];

        foreach ([1964, 1972, 1990] as $year) {
            $photos[] = $this->app->make(AlbumService::class)->addPhoto($album, PlaceholderImage::grey(64, 48), [
                MediaProperties::ALT_TEXT => 'Photo', MediaProperties::YEAR => $year,
            ]);
        }

        $stories = $this->service->stories([$photos[0]]);

        $this->assertSame(Sections::homeStory(), array_column($stories, 'key'));
        $this->assertSame(['About Prof. Mokhidir', 'Publications', 'Tributes'], array_column($stories, 'title'));
        $this->assertSame([route('about'), route('publications'), route('tributes')], array_column($stories, 'url'));
        $this->assertSame([Taglines::STORY_ABOUT, Taglines::STORY_PUBLICATIONS, Taglines::STORY_TRIBUTES], array_column($stories, 'summary'));
        $this->assertSame([$photos[1]->id, $photos[2]->id, $photos[0]->id], array_map(fn (array $story): ?int => $story['image']?->id, $stories));
        $this->assertSame(['I', 'III', 'XI'], array_column($stories, 'numeral'));
    }

    #[Test]
    public function story_cards_without_archive_photos_have_none(): void
    {
        $this->assertSame([null, null, null], array_column($this->service->stories(), 'image'));
    }

    #[Test]
    public function the_parts_are_served_from_the_content_caches(): void
    {
        Profile::factory()->published()->create();
        CvEntry::factory()->published()->create(['start_year' => 1970]);
        Award::factory()->published()->create(['year' => 1980]);
        HomeQuote::factory()->published()->create();

        $this->service->data();

        foreach ([CacheKeys::PROFILE, CacheKeys::CV_ENTRIES, CacheKeys::AWARDS, CacheKeys::ALBUMS, CacheKeys::HOME_QUOTES] as $key) {
            $this->assertTrue(Cache::has($key), $key);
        }
    }
}
