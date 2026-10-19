<?php

namespace Tests\Unit\Services;

use App\Constants\Disks;
use App\Constants\Sections;
use App\Models\Album;
use App\Models\Commentary;
use App\Models\OtherItem;
use App\Models\Profile;
use App\Models\Video;
use App\Services\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SitemapServiceTest extends TestCase
{
    use RefreshDatabase;

    private SitemapService $sitemap;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->sitemap = $this->app->make(SitemapService::class);
    }

    #[Test]
    public function with_no_content_it_lists_the_home_page_every_section_and_the_tribute_form_without_dates(): void
    {
        $entries = $this->sitemap->entries();

        $expected = [route('home')];
        foreach (Sections::all() as $key) {
            $expected[] = route(Sections::definitions()[$key]['route']);
        }
        $expected[] = route('tributes.create');

        $this->assertSame($expected, array_column($entries, 'loc'));
        $this->assertSame(array_fill(0, count($expected), null), array_column($entries, 'lastmod'));
    }

    #[Test]
    public function published_detail_pages_are_added_with_their_last_change_and_drafts_are_left_out(): void
    {
        Video::factory()->published()->create(['slug' => 'lecture', 'updated_at' => '2024-01-02 03:04:05']);
        Video::factory()->draft()->create(['slug' => 'draft-lecture']);
        Album::factory()->published()->create(['slug' => 'field']);
        Commentary::factory()->published()->create(['slug' => 'essay']);
        OtherItem::factory()->published()->create(['slug' => 'notice', 'destination_url' => null, 'body' => '<p>Body</p>']);
        OtherItem::factory()->published()->create(['slug' => 'linked', 'destination_url' => 'https://example.org', 'body' => null]);
        Profile::factory()->published()->create(['updated_at' => '2037-06-07 08:09:10']);

        $entries = collect($this->sitemap->entries())->keyBy('loc');

        $this->assertSame('2024-01-02T03:04:05+00:00', $entries[route('videos.show', 'lecture')]['lastmod']);
        $this->assertArrayNotHasKey(route('videos.show', 'draft-lecture'), $entries->all());
        $this->assertSame('2024-01-02T03:04:05+00:00', $entries[route('videos')]['lastmod'], 'Section takes the newest item');
        $this->assertSame('2037-06-07T08:09:10+00:00', $entries[route('about')]['lastmod']);
        $this->assertSame('2037-06-07T08:09:10+00:00', $entries[route('home')]['lastmod'], 'Home takes the newest change anywhere');
        $this->assertTrue($entries->has(route('pictures.show', 'field')));
        $this->assertTrue($entries->has(route('commentary.show', 'essay')));
        $this->assertTrue($entries->has(route('other.show', 'notice')));
        $this->assertFalse($entries->has(route('other.show', 'linked')));
        $this->assertNull($entries[route('community')]['lastmod']);
    }
}
