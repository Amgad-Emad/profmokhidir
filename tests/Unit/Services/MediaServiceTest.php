<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Album;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\MediaService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaServiceTest extends TestCase
{
    use RefreshDatabase;

    private MediaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        Storage::fake(Disks::PRIVATE);

        $this->service = $this->app->make(MediaService::class);
    }

    #[Test]
    public function attach_stores_normalised_custom_properties(): void
    {
        $album = Album::factory()->create();

        $media = $this->service->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS, [
            MediaProperties::ALT_TEXT => '  Alt ',
            MediaProperties::YEAR => '1999',
            MediaProperties::TREATMENT => MediaTreatment::Portrait,
            MediaProperties::IS_COVER => 1,
        ]);

        $this->assertSame(MediaCollections::PHOTOS, $media->collection_name);
        $this->assertSame(Disks::PUBLIC, $media->disk);
        $this->assertSame('Alt', $media->getCustomProperty(MediaProperties::ALT_TEXT));
        $this->assertSame(1999, $media->getCustomProperty(MediaProperties::YEAR));
        $this->assertSame('portrait', $media->getCustomProperty(MediaProperties::TREATMENT));
        $this->assertTrue($media->getCustomProperty(MediaProperties::IS_COVER));
        Storage::disk(Disks::PUBLIC)->assertExists($media->getPathRelativeToRoot());
    }

    #[Test]
    public function attach_rejects_unknown_property_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->attach(Album::factory()->create(), PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS, ['colour' => 'red']);
    }

    #[Test]
    public function attach_rejects_an_invalid_treatment(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->attach(Album::factory()->create(), PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS, [
            MediaProperties::TREATMENT => 'sepia',
        ]);
    }

    #[Test]
    public function update_properties_sets_and_forgets_values(): void
    {
        $media = $this->service->attach(Album::factory()->create(), PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS, [
            MediaProperties::CAPTION => 'Old',
            MediaProperties::PLACE => 'Khartoum',
        ]);

        $media = $this->service->updateProperties($media, [
            MediaProperties::CAPTION => 'New',
            MediaProperties::PLACE => null,
        ]);

        $this->assertSame('New', $media->fresh()->getCustomProperty(MediaProperties::CAPTION));
        $this->assertFalse($media->fresh()->hasCustomProperty(MediaProperties::PLACE));
    }

    #[Test]
    public function remove_deletes_the_file_and_is_audited(): void
    {
        $actor = User::factory()->create();
        $album = Album::factory()->create();
        $media = $this->service->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS);
        $path = $media->getPathRelativeToRoot();

        $this->assertTrue($this->service->remove($media, $actor));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk(Disks::PUBLIC)->assertMissing($path);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $actor->id, 'action' => AuditAction::MEDIA_DELETED]);
        $this->assertSame(0, $this->service->inCollection($album, MediaCollections::PHOTOS)->count());
    }

    #[Test]
    public function reorder_changes_the_order_column(): void
    {
        $album = Album::factory()->create();
        $first = $this->service->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS);
        $second = $this->service->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS);

        $this->service->reorder([$second->id, $first->id]);

        $this->assertSame([$second->id, $first->id], $this->service->inCollection($album->fresh(), MediaCollections::PHOTOS)->pluck('id')->all());
        $this->assertSame($second->id, $this->service->first($album->fresh(), MediaCollections::PHOTOS)?->id);
    }

    #[Test]
    public function missing_for_publishing_lists_blank_required_properties(): void
    {
        $media = $this->service->attach(Album::factory()->create(), PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS, [
            MediaProperties::ALT_TEXT => 'Alt',
            MediaProperties::CAPTION => '',
            MediaProperties::YEAR => 1990,
        ]);

        $this->assertSame([MediaProperties::CAPTION, MediaProperties::PLACE], $this->service->missingForPublishing($media));

        $media = $this->service->updateProperties($media, [MediaProperties::CAPTION => 'c', MediaProperties::PLACE => 'p']);
        $this->assertSame([], $this->service->missingForPublishing($media));
    }

    #[Test]
    public function move_to_disk_relocates_the_file(): void
    {
        $material = TeachingMaterial::factory()->create();
        $media = $this->service->attach($material, UploadedFile::fake()->create('notes.pdf', 5, 'application/pdf'), MediaCollections::FILE, [], Disks::PUBLIC);
        $this->assertSame(Disks::PUBLIC, $media->disk);

        $moved = $this->service->moveToDisk($media, $material, MediaCollections::FILE, Disks::PRIVATE);

        $this->assertSame(Disks::PRIVATE, $moved->disk);
        Storage::disk(Disks::PRIVATE)->assertExists($moved->getPathRelativeToRoot());
        $this->assertSame(1, $material->fresh()->media()->count());
        $this->assertSame($moved->id, $this->service->first($material->fresh(), MediaCollections::FILE)?->id);
    }

    #[Test]
    public function clear_removes_a_whole_collection(): void
    {
        $album = Album::factory()->create();
        $this->service->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS);
        $this->service->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS);

        $this->service->clear($album, MediaCollections::PHOTOS);

        $this->assertSame(0, $album->fresh()->media()->count());
    }
}
