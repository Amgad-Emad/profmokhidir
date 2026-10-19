<?php

namespace Tests\Unit\Services;

use App\Constants\CacheKeys;
use App\Constants\Disks;
use App\Constants\Sections;
use App\Constants\Taglines;
use App\Models\Award;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\Tribute;
use App\Services\HomePageService;
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
        $data = $this->service->data();

        $this->assertNull($data['profile']);
        $this->assertNull($data['portrait']);
        $this->assertCount(0, $data['publications']);
        $this->assertSame(0, $data['awardCount']);
        $this->assertCount(0, $data['tributes']);
        $this->assertSame([
            'label' => Taglines::HERO_LABEL,
            'line' => Taglines::HERO_LINE,
            'quote' => Taglines::HOME_QUOTE,
            'readStory' => Taglines::READ_STORY,
            'leaveTribute' => Taglines::LEAVE_TRIBUTE,
        ], $data['copy']);
    }

    #[Test]
    public function it_only_uses_published_content_and_the_published_portrait(): void
    {
        Profile::factory()->draft()->create(['short_bio' => 'Draft bio']);
        Publication::factory()->draft()->count(2)->create();
        Award::factory()->draft()->create();
        Tribute::factory()->pending()->create();
        Tribute::factory()->rejected()->create();

        $data = $this->service->data();

        $this->assertNull($data['profile']);
        $this->assertCount(0, $data['publications']);
        $this->assertSame(0, $data['awardCount']);
        $this->assertCount(0, $data['tributes']);

        Cache::flush();
        Profile::query()->delete();

        $profile = Profile::factory()->published()->withPortrait()->create(['short_bio' => 'Published bio']);
        Publication::factory()->published()->count(4)->create();
        Award::factory()->published()->count(5)->create();
        Tribute::factory()->approved()->count(4)->create();

        $data = $this->service->data();

        $this->assertTrue($data['profile']->is($profile));
        $this->assertNotNull($data['portrait']);
        $this->assertTrue($data['portrait']->is($profile->portrait()));
        $this->assertCount(HomePageService::PUBLICATIONS_SHOWN, $data['publications']);
        $this->assertSame(5, $data['awardCount']);
        $this->assertCount(HomePageService::TRIBUTES_SHOWN, $data['tributes']);
    }

    #[Test]
    public function publications_are_the_newest_by_year_and_tributes_the_most_recently_approved(): void
    {
        Publication::factory()->published()->create(['year' => 1990, 'title' => 'Old']);
        Publication::factory()->published()->create(['year' => 2015, 'title' => 'Newest']);
        Publication::factory()->published()->create(['year' => 2001, 'title' => 'Middle']);
        Publication::factory()->published()->create(['year' => 1975, 'title' => 'Oldest']);

        Tribute::factory()->approved()->create(['name' => 'Earlier', 'approved_at' => now()->subDays(10)]);
        Tribute::factory()->approved()->create(['name' => 'Latest', 'approved_at' => now()->subDay()]);

        $data = $this->service->data();

        $this->assertSame(['Newest', 'Middle', 'Old'], $data['publications']->pluck('title')->all());
        $this->assertSame(['Latest', 'Earlier'], $data['tributes']->pluck('name')->all());
    }

    #[Test]
    public function the_section_row_links_the_sections_the_chapters_do_not_cover_in_order(): void
    {
        $sections = $this->service->sections();

        $this->assertSame(Sections::homeRow(), array_column($sections, 'key'));
        $this->assertSame(['Videos', 'Pictures', 'Teaching', 'Commentary', 'Community'], array_column($sections, 'label'));

        foreach ($sections as $item) {
            $this->assertSame(route($item['route']), $item['url']);
            $this->assertFalse($item['active']);
        }
    }

    #[Test]
    public function the_parts_are_served_from_the_content_caches(): void
    {
        Profile::factory()->published()->create();
        Publication::factory()->published()->create();
        Tribute::factory()->approved()->create();

        $this->service->data();

        $this->assertTrue(Cache::has(CacheKeys::PROFILE));
        $this->assertTrue(Cache::has(CacheKeys::PUBLICATIONS.'.latest'));
        $this->assertTrue(Cache::has(CacheKeys::TRIBUTES.'.latest'));
    }
}
