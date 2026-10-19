<?php

namespace Tests\Feature\Seo;

use App\Constants\Disks;
use App\Constants\Sections;
use App\Constants\Seo;
use App\Models\Album;
use App\Models\Commentary;
use App\Models\OtherItem;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sitemap_lists_the_home_page_every_public_section_and_published_detail_pages(): void
    {
        Storage::fake(Disks::PUBLIC);
        $video = Video::factory()->published()->create();
        $album = Album::factory()->published()->create();
        $commentary = Commentary::factory()->published()->create();
        $item = OtherItem::factory()->published()->create(['destination_url' => null, 'body' => '<p>Body</p>']);
        $linked = OtherItem::factory()->published()->create(['destination_url' => 'https://example.org', 'body' => null]);
        $draft = Commentary::factory()->draft()->create();

        $response = $this->get(Seo::SITEMAP_PATH)
            ->assertOk()
            ->assertHeader('Content-Type', Seo::XML_CONTENT_TYPE);

        $xml = simplexml_load_string($response->getContent());
        $this->assertSame(Seo::SITEMAP_NAMESPACE, $xml->getNamespaces()['']);

        $locations = array_map('strval', iterator_to_array($xml->url, false) ? array_map(fn ($url) => $url->loc, iterator_to_array($xml->url, false)) : []);

        $this->assertSame(route('home'), $locations[0]);

        foreach (Sections::all() as $key) {
            $this->assertContains(route(Sections::definitions()[$key]['route']), $locations, $key);
        }

        $this->assertContains(route('tributes.create'), $locations);
        $this->assertContains(route('videos.show', $video->slug), $locations);
        $this->assertContains(route('pictures.show', $album->slug), $locations);
        $this->assertContains(route('commentary.show', $commentary->slug), $locations);
        $this->assertContains(route('other.show', $item->slug), $locations);
        $this->assertNotContains(route('commentary.show', $draft->slug), $locations);
        $this->assertNotContains(route('other.show', $linked->slug), $locations, 'Linked items have no page of their own');
        $this->assertSame($locations, array_unique($locations));

        foreach ($locations as $location) {
            $this->assertStringNotContainsString('/admin', $location);
            $this->assertStringNotContainsString('/community/chat', $location);
            $this->assertStringNotContainsString('/login', $location);
        }
    }

    #[Test]
    public function detail_entries_carry_the_last_change_of_their_content(): void
    {
        $commentary = Commentary::factory()->published()->create();
        $commentary->forceFill(['updated_at' => '2024-03-04 05:06:07'])->saveQuietly();
        app(\App\Services\CommentaryService::class)->flushCache();

        $this->get(route(Seo::SITEMAP_ROUTE))
            ->assertOk()
            ->assertSee('<loc>'.route('commentary.show', $commentary->slug).'</loc>', false)
            ->assertSee('<lastmod>2024-03-04T05:06:07+00:00</lastmod>', false);
    }

    #[Test]
    public function the_sitemap_is_well_formed_with_no_content(): void
    {
        $response = $this->get(route(Seo::SITEMAP_ROUTE))->assertOk();

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertCount(count(Sections::all()) + 2, $xml->url, 'Home, every section and the tribute form');
    }
}
