<?php

namespace Tests\Unit\Repositories;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaConversions;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Album;
use App\Models\ChatMessage;
use App\Models\ChatUpload;
use App\Models\TeachingMaterial;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\Eloquent\SpatieMediaRepository;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Constants\Archive;
use Tests\TestCase;

class SpatieMediaRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private MediaRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        Storage::fake(Disks::PRIVATE);

        // Tests run inside a transaction that never commits, so conversions
        // must not wait for the database commit or they would never run.
        config(['media-library.queue_conversions_after_database_commit' => false]);

        $this->repository = $this->app->make(MediaRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_spatie_implementation(): void
    {
        $this->assertInstanceOf(SpatieMediaRepository::class, $this->repository);
    }

    #[Test]
    public function attach_stores_an_image_with_custom_properties_on_the_public_disk(): void
    {
        $album = Album::factory()->create();

        $media = $this->repository->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS, [
            MediaProperties::ALT_TEXT => 'A grey square',
            MediaProperties::CAPTION => 'Test',
            MediaProperties::PLACE => 'Khartoum',
            MediaProperties::YEAR => 1999,
            MediaProperties::TREATMENT => MediaTreatment::Archive->value,
        ]);

        $this->assertSame(Disks::PUBLIC, $media->disk);
        $this->assertSame(MediaCollections::PHOTOS, $media->collection_name);
        $this->assertSame('A grey square', $media->getCustomProperty(MediaProperties::ALT_TEXT));
        $this->assertSame(1999, $media->getCustomProperty(MediaProperties::YEAR));
        $this->assertSame('archive', $media->getCustomProperty(MediaProperties::TREATMENT));
        Storage::disk(Disks::PUBLIC)->assertExists($media->getPathRelativeToRoot());

        $this->assertTrue($this->repository->find($media->id)->is($media));
        $this->assertTrue($this->repository->findOrFail($media->id)->is($media));
        $this->assertTrue($this->repository->first($album, MediaCollections::PHOTOS)->is($media));
        $this->assertNull($this->repository->first($album, MediaCollections::MEDIA));
        $this->assertSame(1, $this->repository->countFor($album));

        $converted = $media->fresh();

        foreach (MediaConversions::names() as $conversion) {
            $this->assertTrue($converted->hasGeneratedConversion($conversion), "Missing conversion {$conversion}");
            $this->assertStringEndsWith('.webp', $converted->getPath($conversion));
            Storage::disk(Disks::PUBLIC)->assertExists($converted->getPathRelativeToRoot($conversion));
        }
    }

    #[Test]
    public function attach_can_target_the_private_disk_for_members_only_files(): void
    {
        $material = TeachingMaterial::factory()->membersOnly()->create();
        $file = tempnam(sys_get_temp_dir(), 'notes').'.txt';
        file_put_contents($file, 'members-only notes');

        $media = $this->repository->attach($material, $file, MediaCollections::FILE, [], Disks::PRIVATE);

        $this->assertSame(Disks::PRIVATE, $media->disk);
        Storage::disk(Disks::PRIVATE)->assertExists($media->getPathRelativeToRoot());
        Storage::disk(Disks::PUBLIC)->assertMissing($media->getPathRelativeToRoot());
    }

    #[Test]
    public function preserving_original_keeps_the_source_file(): void
    {
        $album = Album::factory()->create();
        $path = PlaceholderImage::grey(64, 64);

        $this->repository->attach($album, $path, MediaCollections::PHOTOS, [], null, false);
        $this->assertFileDoesNotExist($path);

        $kept = PlaceholderImage::grey(64, 64);
        $this->repository->attach($album, $kept, MediaCollections::PHOTOS, [], null, true);
        $this->assertFileExists($kept);
        @unlink($kept);
    }

    #[Test]
    public function update_properties_sets_values_and_forgets_nulls(): void
    {
        $album = Album::factory()->create();
        $media = $this->repository->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS, [
            MediaProperties::CAPTION => 'Old',
            MediaProperties::CREDIT => 'Someone',
        ]);

        $this->repository->updateProperties($media, [
            MediaProperties::CAPTION => 'New',
            MediaProperties::CREDIT => null,
            MediaProperties::IS_COVER => true,
        ]);

        $fresh = $media->fresh();
        $this->assertSame('New', $fresh->getCustomProperty(MediaProperties::CAPTION));
        $this->assertFalse($fresh->hasCustomProperty(MediaProperties::CREDIT));
        $this->assertTrue($fresh->getCustomProperty(MediaProperties::IS_COVER));
    }

    #[Test]
    public function reorder_changes_the_order_column_and_the_collection_order(): void
    {
        $album = Album::factory()->create();
        $first = $this->repository->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS);
        $second = $this->repository->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS);
        $third = $this->repository->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS);

        $this->assertSame([$first->id, $second->id, $third->id], $this->repository->inCollection($album, MediaCollections::PHOTOS)->pluck('id')->all());

        $this->repository->reorder([$third->id, $first->id, $second->id]);

        $this->assertSame(1, $third->fresh()->order_column);
        $this->assertSame(2, $first->fresh()->order_column);
        $this->assertSame(3, $second->fresh()->order_column);
        $this->assertSame([$third->id, $first->id, $second->id], $this->repository->inCollection($album->fresh(), MediaCollections::PHOTOS)->pluck('id')->all());
    }

    #[Test]
    public function delete_and_clear_collection_remove_rows_and_files(): void
    {
        $album = Album::factory()->create();
        $one = $this->repository->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS);
        $two = $this->repository->attach($album, PlaceholderImage::grey(64, 64), MediaCollections::PHOTOS);
        $onePath = $one->getPathRelativeToRoot();

        $this->assertTrue($this->repository->delete($one));
        $this->assertNull(Media::query()->find($one->id));
        Storage::disk(Disks::PUBLIC)->assertMissing($onePath);
        $this->assertSame(1, $this->repository->countFor($album->fresh()));

        $this->repository->clearCollection($album->fresh(), MediaCollections::PHOTOS);

        $this->assertNull(Media::query()->find($two->id));
        $this->assertSame(0, $this->repository->countFor($album->fresh()));
    }

    #[Test]
    public function move_to_disk_relocates_a_file_from_public_to_private(): void
    {
        $material = TeachingMaterial::factory()->create();
        $file = tempnam(sys_get_temp_dir(), 'slides').'.txt';
        file_put_contents($file, 'slides');
        $media = $this->repository->attach($material, $file, MediaCollections::FILE, [], Disks::PUBLIC);
        $publicPath = $media->getPathRelativeToRoot();

        $moved = $this->repository->moveToDisk($media, $material, MediaCollections::FILE, Disks::PRIVATE);

        $this->assertSame(Disks::PRIVATE, $moved->disk);
        $this->assertSame(MediaCollections::FILE, $moved->collection_name);
        Storage::disk(Disks::PRIVATE)->assertExists($moved->getPathRelativeToRoot());
        Storage::disk(Disks::PUBLIC)->assertMissing($publicPath);
        $this->assertNull(Media::query()->find($media->id));
        $this->assertSame(1, $this->repository->countFor($material->fresh()));
        $this->assertSame('slides', Storage::disk(Disks::PRIVATE)->get($moved->getPathRelativeToRoot()));
    }

    #[Test]
    public function archive_columns_are_set_without_touching_the_custom_properties(): void
    {
        $album = Album::factory()->create();
        $media = $this->repository->attach($album, PlaceholderImage::grey(40, 30), MediaCollections::PHOTOS, [MediaProperties::CAPTION => 'Kept']);

        $this->assertNull($media->getAttribute(Archive::DECADE));
        $this->assertFalse((bool) $media->getAttribute(Archive::FEATURED));

        $updated = $this->repository->updateArchiveFields($media, 1960, true);

        $this->assertSame(1960, (int) $updated->fresh()->getAttribute(Archive::DECADE));
        $this->assertTrue((bool) $updated->fresh()->getAttribute(Archive::FEATURED));
        $this->assertSame('Kept', $updated->fresh()->getCustomProperty(MediaProperties::CAPTION));
    }

    #[Test]
    public function reassign_hands_a_file_to_another_model_without_moving_it(): void
    {
        $upload = ChatUpload::factory()->create();
        $media = $this->repository->attach($upload, UploadedFile::fake()->create('notes.pdf', 5, 'application/pdf'), MediaCollections::CHAT_PENDING, [], Disks::PRIVATE);
        $path = $media->getPathRelativeToRoot();
        $message = ChatMessage::factory()->create();

        $moved = $this->repository->reassign($media, $message, MediaCollections::CHAT_ATTACHMENTS, [MediaProperties::ALT_TEXT => 'Notes']);

        $this->assertSame([$message->getMorphClass(), $message->id, MediaCollections::CHAT_ATTACHMENTS], [$moved->model_type, (int) $moved->model_id, $moved->collection_name]);
        $this->assertSame('Notes', $moved->getCustomProperty(MediaProperties::ALT_TEXT));
        $this->assertSame($path, $moved->fresh()->getPathRelativeToRoot());
        Storage::disk(Disks::PRIVATE)->assertExists($path);
        $this->assertCount(1, $message->fresh()->attachments());
        $this->assertCount(0, $upload->fresh()->getMedia(MediaCollections::CHAT_PENDING));
    }
}

