<?php

namespace Tests\Feature\Components;

use App\Constants\Disks;
use App\Constants\VideoEmbed;
use App\Constants\VideoProvider;
use App\Models\Video;
use App\Services\VideoService;
use App\Services\VideosPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VideoPlayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function the_embed_facade_keeps_the_iframe_inside_a_template_and_links_out_without_javascript(): void
    {
        $html = $this->render(Video::factory()->provider(VideoProvider::YouTube, 'https://youtu.be/dQw4w9WgXcQ')->create(['title' => 'A <lecture>']));

        $this->assertStringContainsString('<div class="video-player">', $html);
        $this->assertStringContainsString('class="video-facade on-dark relative overflow-hidden rounded-card bg-evergreen" style="aspect-ratio: 16 / 9" x-data="videoFacade"', $html);
        $this->assertStringContainsString('x-show="! playing"', $html);
        $this->assertStringContainsString('href="https://youtu.be/dQw4w9WgXcQ" rel="noopener" class="video-facade__play" @click.prevent="play()"', $html);
        $this->assertStringContainsString('<span class="sr-only">A &lt;lecture&gt;</span>', $html);
        $this->assertMatchesRegularExpression('/<template x-if="playing">\s*<iframe\s+x-ref="frame"/', $html);
        $this->assertStringContainsString('src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&amp;rel=0&amp;modestbranding=1"', $html);
        $this->assertStringContainsString('title="A &lt;lecture&gt;"', $html);
        $this->assertStringContainsString('allow="'.VideoEmbed::IFRAME_ALLOW.'"', $html);
        $this->assertStringContainsString('referrerpolicy="strict-origin-when-cross-origin"', $html);
        $this->assertStringContainsString('Watch on YouTube · youtu.be', $html);
        $this->assertStringContainsString('glow-brass', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    #[Test]
    public function the_file_player_uses_native_controls_without_preloading(): void
    {
        $video = Video::factory()->provider(VideoProvider::File)->create(['media_url' => null, 'captions_url' => 'https://cdn.example.org/talk.vtt', 'title' => 'Talk']);
        $file = $this->app->make(VideoService::class)->setVideoFile($video, UploadedFile::fake()->create('talk.webm', 20, 'video/webm'));

        $html = $this->render($video->fresh());

        $this->assertMatchesRegularExpression('/<video\s+controls\s+preload="none"\s+playsinline/', $html);
        $this->assertStringNotContainsString('poster=', $html);
        $this->assertStringContainsString('<source src="'.$file->getUrl().'" type="video/webm">', $html);
        $this->assertStringContainsString('<track kind="captions" src="https://cdn.example.org/talk.vtt" srclang="en" label="Captions" default>', $html);
        $this->assertStringContainsString('<a href="'.$file->getUrl().'" class="text-ivory">Download the file</a>', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    #[Test]
    public function the_link_player_is_a_pill_link_naming_the_destination(): void
    {
        $html = $this->render(Video::factory()->provider(VideoProvider::Link, 'https://www.example.org/talk')->create(['title' => 'Talk']));

        $this->assertStringContainsString('This recording is hosted on another site.', $html);
        $this->assertMatchesRegularExpression('/<a href="https:\/\/www\.example\.org\/talk" class="[^"]*rounded-full[^"]*" rel="noopener">Watch on example\.org<\/a>/', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('<video', $html);
    }

    private function render(Video $video): string
    {
        $player = $this->app->make(VideosPageService::class)->player($video);

        return Blade::render('<x-video-player :player="$player" :title="$title" />', ['player' => $player, 'title' => $video->title]);
    }
}
