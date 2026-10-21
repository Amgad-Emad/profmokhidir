<?php

namespace Tests\Unit\Services;

use App\Constants\CacheKeys;
use App\Constants\Disks;
use App\Constants\VideoEmbed;
use App\Constants\VideoProvider;
use App\Models\Video;
use App\Services\VideoService;
use App\Services\VideosPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VideosPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private VideosPageService $page;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        $this->page = $this->app->make(VideosPageService::class);
    }

    #[Test]
    public function the_index_opens_with_the_first_recording_and_lists_the_rest_in_display_order_from_the_cache(): void
    {
        $second = Video::factory()->published()->create(['sort_order' => 2]);
        $first = Video::factory()->published()->provider(VideoProvider::YouTube, 'https://youtu.be/dQw4w9WgXcQ')->create(['sort_order' => 1]);
        Video::factory()->draft()->create(['sort_order' => 0]);

        $data = $this->page->index();

        $this->assertSame('videos', $data['section']['key']);
        $this->assertTrue($data['section']['active']);
        $this->assertTrue($data['featured']->is($first));
        $this->assertSame(VideoEmbed::KIND_EMBED, $data['player']['kind']);
        $this->assertSame([$second->id], $data['videos']->pluck('id')->all());
        $this->assertTrue(Cache::has(CacheKeys::VIDEOS));
    }

    #[Test]
    public function a_flagged_recording_is_featured_ahead_of_the_first_and_an_empty_hall_has_none(): void
    {
        $this->assertNull($this->page->index()['featured']);
        $this->assertNull($this->page->index()['player']);

        Video::factory()->published()->create(['sort_order' => 1]);
        $flagged = Video::factory()->published()->featured()->create(['sort_order' => 3]);
        Video::factory()->draft()->featured()->create(['sort_order' => 0]);
        $this->app->make(VideoService::class)->flushCache();

        $data = $this->page->index();

        $this->assertTrue($data['featured']->is($flagged));
        $this->assertFalse($data['videos']->contains($flagged));
        $this->assertCount(1, $data['videos']);
    }

    #[Test]
    public function the_detail_page_lists_other_recordings_from_the_same_event(): void
    {
        $video = Video::factory()->published()->create(['event' => 'Inaugural Lecture', 'sort_order' => 1]);
        $same = Video::factory()->published()->create(['event' => 'inaugural lecture ', 'sort_order' => 2]);
        Video::factory()->published()->create(['event' => 'Alumni Gathering', 'sort_order' => 3]);
        Video::factory()->draft()->create(['event' => 'Inaugural Lecture']);

        $data = $this->page->show($video);

        $this->assertSame([$same->id], $data['related']->pluck('id')->all());
        $this->assertSame(['Inaugural Lecture', $video->location], $this->page->labelParts($video));
        $this->assertSame([], $this->page->labelParts(Video::factory()->make(['event' => null, 'location' => ' '])));
    }

    #[Test]
    public function find_returns_published_videos_only(): void
    {
        $video = Video::factory()->published()->create(['slug' => 'talk']);
        Video::factory()->draft()->create(['slug' => 'draft']);

        $this->assertTrue($this->page->find('talk')->is($video));
        $this->assertNull($this->page->find('draft'));
        $this->assertNull($this->page->find('missing'));
    }

    #[Test]
    public function hosted_videos_get_a_cookie_free_embed_behind_the_poster(): void
    {
        $video = Video::factory()->published()->withThumbnail()->provider(VideoProvider::YouTube, 'https://youtu.be/dQw4w9WgXcQ')->create([
            'description' => str_repeat('A long description. ', 20),
        ]);

        $data = $this->page->show($video->fresh());

        $this->assertSame('videos.show', $data['section']['route'].'.show');
        $this->assertTrue($data['section']['active']);
        $this->assertSame(VideoEmbed::KIND_EMBED, $data['player']['kind']);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0&modestbranding=1', $data['player']['embed_url']);
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $data['player']['url']);
        $this->assertSame('youtu.be', $data['player']['host']);
        $this->assertSame('Watch on YouTube', $data['player']['label']);
        $this->assertNotNull($data['player']['poster']);
        $this->assertLessThanOrEqual(VideosPageService::DESCRIPTION_LIMIT + 3, mb_strlen($data['description']));
        $this->assertStringEndsWith('...', $data['description']);

        $vimeo = Video::factory()->published()->provider(VideoProvider::Vimeo, 'https://vimeo.com/76979871')->create();
        $player = $this->page->player($vimeo);

        $this->assertSame(VideoEmbed::KIND_EMBED, $player['kind']);
        $this->assertSame('https://player.vimeo.com/video/76979871?dnt=1&autoplay=1', $player['embed_url']);
        $this->assertSame('Watch on Vimeo', $player['label']);
        $this->assertNull($player['poster']);
    }

    #[Test]
    public function uploaded_files_play_natively_with_their_captions(): void
    {
        $video = Video::factory()->provider(VideoProvider::File)->create(['media_url' => null, 'captions_url' => ' /captions/talk.vtt ']);
        $file = $this->app->make(VideoService::class)->setVideoFile($video, UploadedFile::fake()->create('talk.mp4', 20, 'video/mp4'));

        $player = $this->page->player($video->fresh());

        $this->assertSame(VideoEmbed::KIND_FILE, $player['kind']);
        $this->assertTrue($player['file']->is($file));
        $this->assertSame('video/mp4', $player['mime']);
        $this->assertSame('/captions/talk.vtt', $player['captions_url']);
        $this->assertNull($player['embed_url']);
    }

    #[Test]
    public function links_and_unparseable_hosted_urls_fall_back_to_a_plain_link(): void
    {
        $link = Video::factory()->provider(VideoProvider::Link, 'https://www.example.org/archive/talk')->create();
        $player = $this->page->player($link);

        $this->assertSame(VideoEmbed::KIND_LINK, $player['kind']);
        $this->assertSame('https://www.example.org/archive/talk', $player['url']);
        $this->assertSame('Watch on example.org', $player['label']);

        $odd = Video::factory()->provider(VideoProvider::YouTube, 'https://www.youtube.com/channel/UC123')->create();
        $this->assertSame(VideoEmbed::KIND_LINK, $this->page->player($odd)['kind']);
        $this->assertSame('Watch on youtube.com', $this->page->player($odd)['label']);

        $fileless = Video::factory()->provider(VideoProvider::File)->create(['media_url' => null]);
        $player = $this->page->player($fileless);

        $this->assertSame(VideoEmbed::KIND_LINK, $player['kind']);
        $this->assertNull($player['url']);
        $this->assertSame('Watch the video', $player['label']);

        $this->assertNull($this->page->show(Video::factory()->create(['description' => '  ']))['description']);
        $this->assertSame(route('videos.show', $link->slug), $this->page->detailUrl($link));
    }
}
