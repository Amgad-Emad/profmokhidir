<?php

namespace Tests\Feature\Media;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaConversions;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Album;
use App\Models\OtherItem;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\Video;
use App\Services\AlbumService;
use App\Services\ProfileService;
use App\Services\TeachingMaterialService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\HasMedia;
use Tests\TestCase;

class MediaCollectionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        Storage::fake(Disks::PRIVATE);
    }

    #[Test]
    public function every_media_model_registers_the_expected_collections(): void
    {
        $expected = [
            Profile::class => [MediaCollections::PORTRAIT],
            Publication::class => [MediaCollections::FILE],
            Video::class => [MediaCollections::VIDEO, MediaCollections::THUMBNAIL],
            Album::class => [MediaCollections::PHOTOS],
            TeachingMaterial::class => [MediaCollections::FILE],
            OtherItem::class => [MediaCollections::MEDIA],
        ];

        foreach ($expected as $class => $collections) {
            /** @var HasMedia $model */
            $model = new $class;
            $model->registerMediaCollections();

            $this->assertEqualsCanonicalizing(
                $collections,
                collect($model->mediaCollections)->pluck('name')->all(),
                $class,
            );
        }
    }

    #[Test]
    public function image_collections_get_queued_responsive_webp_conversions_at_three_widths(): void
    {
        $album = new Album;
        $album->registerMediaConversions();

        $conversions = collect($album->mediaConversions);

        $this->assertSame(MediaConversions::names(), $conversions->map->getName()->all());

        foreach ($conversions as $conversion) {
            $this->assertTrue($conversion->shouldBeQueued());
            $this->assertTrue($conversion->shouldGenerateResponsiveImages());
            $this->assertTrue($conversion->shouldBePerformedOn(MediaCollections::PHOTOS));
            $this->assertSame('webp', $conversion->getResultExtension('png'));
        }

        $this->assertSame([480, 960, 1600], array_values(MediaConversions::widths()));
    }

    #[Test]
    public function attaching_a_portrait_generates_the_webp_conversions_and_stores_the_custom_properties(): void
    {
        $profile = Profile::factory()->published()->create();

        $media = app(ProfileService::class)->setPortrait($profile, PlaceholderImage::grey(64, 64), [
            MediaProperties::ALT_TEXT => 'Portrait of the professor',
            MediaProperties::CAPTION => 'In his office',
            MediaProperties::PLACE => 'Khartoum',
            MediaProperties::YEAR => '1998',
            MediaProperties::CREDIT => 'Family archive',
            MediaProperties::PERMISSIONS_NOTE => 'Family permission on file',
        ]);

        $media->refresh();

        $this->assertSame(MediaCollections::PORTRAIT, $media->collection_name);
        $this->assertSame(Disks::PUBLIC, $media->disk);
        $this->assertSame(MediaTreatment::Portrait->value, $media->getCustomProperty(MediaProperties::TREATMENT));
        $this->assertSame(1998, $media->getCustomProperty(MediaProperties::YEAR));
        $this->assertSame('Family permission on file', $media->getCustomProperty(MediaProperties::PERMISSIONS_NOTE));

        foreach (MediaConversions::names() as $name) {
            $this->assertTrue($media->hasGeneratedConversion($name), "missing conversion {$name}");
            $this->assertStringEndsWith('.webp', $media->getPath($name));
            Storage::disk(Disks::PUBLIC)->assertExists(str_replace(Storage::disk(Disks::PUBLIC)->path(''), '', $media->getPath($name)));
        }

        // A portrait is a single file: a second upload replaces the first.
        app(ProfileService::class)->setPortrait($profile, PlaceholderImage::grey(64, 64));
        $this->assertSame(1, $profile->fresh()->getMedia(MediaCollections::PORTRAIT)->count());
    }

    #[Test]
    public function album_photos_are_ordered_and_the_cover_is_the_flagged_photo_or_the_first(): void
    {
        $album = Album::factory()->published()->withPhotos(3, 64, 64)->create();
        $photos = $album->photos();

        $this->assertSame([1, 2, 3], $photos->pluck('order_column')->all());
        $this->assertSame($photos->first()->id, $album->cover()?->id);

        app(AlbumService::class)->setCover($album, $photos->last());

        $this->assertSame($photos->last()->id, $album->fresh()->cover()?->id);
        $this->assertSame(1, $album->fresh()->photos()->filter(fn ($photo) => $photo->getCustomProperty(MediaProperties::IS_COVER))->count());
    }

    #[Test]
    public function members_only_teaching_files_live_on_the_private_disk_and_public_ones_on_the_public_disk(): void
    {
        $service = app(TeachingMaterialService::class);

        $members = TeachingMaterial::factory()->published()->membersOnly()->create();
        $public = TeachingMaterial::factory()->published()->create();

        $private = $service->attachFile($members, UploadedFile::fake()->create('exercise.pdf', 12, 'application/pdf'));
        $open = $service->attachFile($public, UploadedFile::fake()->create('slides.pdf', 12, 'application/pdf'));

        $this->assertSame(Disks::PRIVATE, $private->disk);
        $this->assertSame(Disks::PUBLIC, $open->disk);
        Storage::disk(Disks::PRIVATE)->assertExists($private->id.'/exercise.pdf');
        Storage::disk(Disks::PUBLIC)->assertExists($open->id.'/slides.pdf');
        Storage::disk(Disks::PUBLIC)->assertMissing($private->id.'/exercise.pdf');
    }

    #[Test]
    public function the_private_disk_is_not_publicly_served(): void
    {
        $this->assertSame('private', config('filesystems.disks.private.visibility'));
        $this->assertFalse((bool) config('filesystems.disks.private.serve'));
        $this->assertArrayNotHasKey('url', config('filesystems.disks.private'));
        $this->assertSame(Disks::PUBLIC, config('media-library.disk_name'));
    }
}
