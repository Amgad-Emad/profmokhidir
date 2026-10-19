<?php

namespace Tests\Unit\Services;

use App\Constants\CacheKeys;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\OtherItem;
use App\Services\OtherItemService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtherItemServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtherItemService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(OtherItemService::class);
    }

    #[Test]
    public function create_generates_unique_slugs(): void
    {
        $first = $this->service->create(['title' => 'Memorial Lecture']);
        $second = $this->service->create(['title' => 'Memorial Lecture']);

        $this->assertSame('memorial-lecture', $first->slug);
        $this->assertSame('memorial-lecture-2', $second->slug);
    }

    #[Test]
    public function published_in_category_and_categories_work_and_are_cached(): void
    {
        OtherItem::factory()->published()->category('Archive')->create();
        OtherItem::factory()->published()->category('Archive')->create();
        OtherItem::factory()->published()->category('In the press')->create();
        OtherItem::factory()->draft()->category('Hidden')->create();

        $this->assertCount(2, $this->service->publishedInCategory('Archive'));
        $this->assertSame(['Archive', 'In the press'], $this->service->categories());
        $this->assertTrue(Cache::has(CacheKeys::OTHER_ITEMS.'.categories'));

        $this->service->create(['title' => 'X', 'category' => 'Announcements', 'status' => 'published']);
        $this->assertFalse(Cache::has(CacheKeys::OTHER_ITEMS.'.categories'));
        $this->assertSame(['Announcements', 'Archive', 'In the press'], $this->service->categories());
    }

    #[Test]
    public function media_can_be_added_and_removed(): void
    {
        $item = OtherItem::factory()->create();

        $media = $this->service->addMedia($item, PlaceholderImage::grey(64, 64), [MediaProperties::ALT_TEXT => 'Alt']);

        $this->assertSame(MediaCollections::MEDIA, $media->collection_name);
        $this->assertSame(MediaTreatment::Archive->value, $media->getCustomProperty(MediaProperties::TREATMENT));
        $this->assertSame(1, $item->fresh()->getMedia(MediaCollections::MEDIA)->count());

        $this->service->removeMedia($media);
        $this->assertSame(0, $item->fresh()->getMedia(MediaCollections::MEDIA)->count());
    }

    #[Test]
    public function find_published_by_slug_ignores_drafts(): void
    {
        $published = OtherItem::factory()->published()->create(['slug' => 'item']);
        OtherItem::factory()->draft()->create(['slug' => 'draft']);

        $this->assertTrue($this->service->findPublishedBySlug('item')->is($published));
        $this->assertNull($this->service->findPublishedBySlug('draft'));
    }
}
