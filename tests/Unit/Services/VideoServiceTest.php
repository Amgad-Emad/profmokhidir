<?php

namespace Tests\Unit\Services;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Constants\VideoProvider;
use App\Models\Video;
use App\Services\VideoService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VideoServiceTest extends TestCase
{
    use RefreshDatabase;

    private VideoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->service = $this->app->make(VideoService::class);
    }

    #[Test]
    public function create_generates_a_unique_slug_and_casts_the_provider(): void
    {
        $first = $this->service->create(['title' => 'Inaugural Lecture', 'provider' => 'youtube', 'media_url' => 'https://youtu.be/x']);
        $second = $this->service->create(['title' => 'Inaugural Lecture', 'provider' => VideoProvider::Vimeo]);

        $this->assertSame('inaugural-lecture', $first->slug);
        $this->assertSame('inaugural-lecture-2', $second->slug);
        $this->assertSame(VideoProvider::YouTube, $first->provider);
        $this->assertSame(VideoProvider::Vimeo, $second->provider);
    }

    #[Test]
    public function set_thumbnail_defaults_to_the_archive_treatment(): void
    {
        $video = Video::factory()->create();

        $media = $this->service->setThumbnail($video, PlaceholderImage::grey(64, 64), [MediaProperties::ALT_TEXT => 'Still']);

        $this->assertSame(MediaCollections::THUMBNAIL, $media->collection_name);
        $this->assertSame(MediaTreatment::Archive->value, $media->getCustomProperty(MediaProperties::TREATMENT));
        $this->assertTrue($video->fresh()->thumbnail()->is($media));
    }

    #[Test]
    public function set_video_file_and_remove_media_manage_the_collections(): void
    {
        $video = Video::factory()->create();

        $file = $this->service->setVideoFile($video, UploadedFile::fake()->create('talk.mp4', 20, 'video/mp4'));
        $this->assertSame(MediaCollections::VIDEO, $file->collection_name);
        $this->assertTrue($video->fresh()->videoFile()->is($file));

        $this->service->setThumbnail($video, PlaceholderImage::grey(64, 64));
        $this->service->removeMedia($video, MediaCollections::VIDEO);

        $this->assertNull($video->fresh()->videoFile());
        $this->assertNotNull($video->fresh()->thumbnail());
    }

    #[Test]
    public function find_published_by_slug_ignores_drafts(): void
    {
        $published = Video::factory()->published()->create(['slug' => 'talk']);
        Video::factory()->draft()->create(['slug' => 'draft-talk']);

        $this->assertTrue($this->service->findPublishedBySlug('talk')->is($published));
        $this->assertNull($this->service->findPublishedBySlug('draft-talk'));
    }
}
