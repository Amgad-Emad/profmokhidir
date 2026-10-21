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
use App\Constants\Archive;
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

    #[Test]
    public function the_featured_recording_is_the_flagged_one_or_else_the_first(): void
    {
        $this->assertNull($this->service->featured());

        $first = Video::factory()->published()->create(['sort_order' => 1]);
        $this->service->flushCache();
        $this->assertTrue($this->service->featured()->is($first));

        $flagged = Video::factory()->published()->featured()->create(['sort_order' => 2]);
        $this->service->flushCache();
        $this->assertTrue($this->service->featured()->is($flagged));
    }

    #[Test]
    public function recordings_from_the_same_event_are_matched_loosely_and_limited(): void
    {
        $video = Video::factory()->published()->create(['event' => 'Inaugural Lecture', 'sort_order' => 1]);
        $others = Video::factory()->count(4)->published()->create(['event' => ' inaugural lecture', 'sort_order' => 2]);
        Video::factory()->published()->create(['event' => 'Other', 'sort_order' => 3]);

        $related = $this->service->fromSameEvent($video);

        $this->assertCount(Archive::RELATED_LIMIT, $related);
        $this->assertFalse($related->contains($video));
        $this->assertTrue($others->contains($related->first()));
        $this->assertCount(4, $this->service->fromSameEvent($video, 10));
        $this->assertCount(0, $this->service->fromSameEvent(Video::factory()->published()->create(['event' => null])));
    }

    #[Test]
    public function the_decade_is_the_override_or_the_dates_decade_and_the_archive_fields_are_cast(): void
    {
        $video = $this->service->create(['title' => 'Talk', 'provider' => 'link', 'media_url' => 'https://x.example', 'date' => '1987-05-01', 'decade' => '', 'is_featured' => '1']);

        $this->assertNull($video->decade);
        $this->assertTrue($video->is_featured);
        $this->assertSame(1980, $this->service->decadeOf($video));

        $video = $this->service->update($video, ['decade' => '1970', 'is_featured' => 0]);

        $this->assertSame(1970, $video->decade);
        $this->assertFalse($video->is_featured);
        $this->assertSame(1970, $this->service->decadeOf($video));
        $this->assertNull($this->service->decadeOf(Video::factory()->make(['date' => null, 'decade' => null])));
    }
}
