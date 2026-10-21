<?php

namespace Tests\Feature\Pages;

use App\Constants\Disks;
use App\Constants\MediaConversions;
use App\Constants\VideoProvider;
use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VideosPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
    }

    #[Test]
    public function the_index_renders_an_empty_state_without_published_videos(): void
    {
        Video::factory()->draft()->create(['title' => 'Draft recording']);

        $this->get(route('videos'))
            ->assertOk()
            ->assertSee('<title>Videos · ', false)
            ->assertSeeInOrder(['section-header', 'Videos', 'rule-brass', 'id="section-title"', 'Videos'], false)
            ->assertSee('Recordings of lectures, interviews and events will appear here once they are published.')
            ->assertDontSee('Draft recording')
            ->assertDontSee('livewire', false);
    }

    #[Test]
    public function the_index_shows_a_card_per_video_with_thumbnail_label_line_title_and_description(): void
    {
        $withThumbnail = Video::factory()->published()->withThumbnail()->create([
            'title' => 'Inaugural lecture', 'slug' => 'inaugural', 'date' => '1983-03-12', 'event' => 'Inaugural Lecture', 'location' => 'Khartoum',
            'description' => 'On the soil beneath our feet.', 'sort_order' => 1,
        ]);
        Video::factory()->published()->create([
            'title' => 'Bare interview', 'slug' => 'bare', 'date' => null, 'event' => null, 'location' => 'Wad Madani', 'description' => null, 'sort_order' => 2,
        ]);
        $thumbnail = $withThumbnail->fresh()->thumbnail();

        $response = $this->get(route('videos'))->assertOk();

        $response
            ->assertSeeInOrder([
                'data-rise-group',
                'media-card', 'href="'.route('videos.show', 'inaugural').'"',
                'aspect-ratio: 16 / 9', 'srcset="'.$thumbnail->getUrl(MediaConversions::SMALL).' 480w', 'width="960" height="540"', 'photo-archive', 'loading="lazy"',
                '<time datetime="1983-03-12">12 March 1983</time>', '· Inaugural Lecture', '· Khartoum',
                'Inaugural lecture', 'On the soil beneath our feet.',
                'href="'.route('videos.show', 'bare').'"', 'media-card__placeholder', 'Wad Madani', 'Bare interview',
            ], false)
            ->assertDontSee('Recordings of lectures, interviews and events will appear here')
            ->assertDontSee('youtube', false)
            ->assertDontSee('<iframe', false);

        // Thumbnail captions belong to the detail page, not the card.
        $this->assertStringNotContainsString('<figcaption', $response->getContent());
    }

    #[Test]
    public function a_hosted_video_renders_the_facade_and_loads_nothing_third_party_until_play(): void
    {
        $video = Video::factory()->published()->withThumbnail()->provider(VideoProvider::YouTube, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')->create([
            'title' => 'Inaugural lecture', 'slug' => 'inaugural', 'date' => '1983-03-12', 'event' => 'Inaugural Lecture', 'location' => 'Khartoum',
            'description' => 'On the soil beneath our feet.',
        ]);

        $response = $this->get(route('videos.show', 'inaugural'))->assertOk();

        $response
            ->assertSee('<title>Inaugural lecture · ', false)
            ->assertSee('<meta name="description" content="On the soil beneath our feet.">', false)
            ->assertSeeInOrder([
                'section-header', 'Videos', 'id="section-title"', 'Inaugural lecture',
                '<time datetime="1983-03-12">12 March 1983</time>', '· Inaugural Lecture', '· Khartoum',
                'video-facade', 'x-data="videoFacade"', 'photo-archive',
                'href="https://www.youtube.com/watch?v=dQw4w9WgXcQ" rel="noopener" class="video-facade__play" @click.prevent="play()"',
                'Play', 'Inaugural lecture',
                '<template x-if="playing">', 'src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&amp;rel=0&amp;modestbranding=1"',
                'title="Inaugural lecture"', 'allow="autoplay; encrypted-media; fullscreen; picture-in-picture"', 'allowfullscreen',
                'Watch on YouTube · youtube.com',
                'On the soil beneath our feet.',
                'aria-label="More videos"', 'href="'.route('videos').'"', 'All videos',
            ], false)
            ->assertDontSee('livewire', false);

        $html = $response->getContent();
        // Everything the browser fetches on page view; the iframe is inert inside <template>.
        preg_match_all('/<(?:img|script|link|video|source|track)\b[^>]*\b(?:src|href|poster)="([^"]+)"/', $html, $matches);

        foreach ($matches[1] as $url) {
            $this->assertStringNotContainsString('youtube', $url, 'Nothing from YouTube loads on page view');
        }

        // The embed exists only inside the <template>.
        $this->assertSame(1, substr_count($html, '<iframe'));
        $this->assertMatchesRegularExpression('/<template x-if="playing">\s*<iframe/', $html);
    }

    #[Test]
    public function a_vimeo_video_embeds_with_do_not_track(): void
    {
        Video::factory()->published()->provider(VideoProvider::Vimeo, 'https://vimeo.com/76979871')->create(['slug' => 'interview', 'title' => 'Interview']);

        $this->get(route('videos.show', 'interview'))
            ->assertOk()
            ->assertSee('src="https://player.vimeo.com/video/76979871?dnt=1&amp;autoplay=1"', false)
            ->assertSee('glow-brass', false)
            ->assertSee('Watch on Vimeo · vimeo.com')
            ->assertDontSee('<img', false);
    }

    #[Test]
    public function an_uploaded_file_plays_in_a_video_element_with_captions_and_no_preloading(): void
    {
        $video = Video::factory()->published()->withThumbnail()->provider(VideoProvider::File)->create([
            'slug' => 'keynote', 'title' => 'Keynote', 'media_url' => null, 'captions_url' => 'https://cdn.example.org/keynote.vtt',
        ]);
        $file = $this->app->make(VideoService::class)->setVideoFile($video, UploadedFile::fake()->create('keynote.mp4', 20, 'video/mp4'));
        $poster = $video->fresh()->thumbnail();

        $this->get(route('videos.show', 'keynote'))
            ->assertOk()
            ->assertSeeInOrder([
                '<video', 'controls', 'preload="none"', 'poster="'.$poster->getUrl(MediaConversions::LARGE).'"',
                '<source src="'.$file->getUrl().'" type="video/mp4">',
                '<track kind="captions" src="https://cdn.example.org/keynote.vtt" srclang="en" label="Captions" default>',
                'Download the file',
            ], false)
            ->assertDontSee('<iframe', false)
            ->assertDontSee('video-facade', false);

        $this->app->make(VideoService::class)->update($video, ['captions_url' => null]);
        $this->get(route('videos.show', 'keynote'))->assertOk()->assertDontSee('<track', false);
    }

    #[Test]
    public function a_plain_link_opens_the_external_page_with_a_clear_label(): void
    {
        Video::factory()->published()->provider(VideoProvider::Link, 'https://www.example.org/archive/talk')->create(['slug' => 'archive-talk', 'title' => 'Archive talk']);

        $this->get(route('videos.show', 'archive-talk'))
            ->assertOk()
            ->assertSee('This recording is hosted on another site.')
            ->assertSee('href="https://www.example.org/archive/talk"', false)
            ->assertSee('rel="noopener"', false)
            ->assertSee('Watch on example.org')
            ->assertDontSee('<iframe', false)
            ->assertDontSee('<video', false);
    }

    #[Test]
    public function the_detail_page_omits_empty_fields_and_hides_drafts(): void
    {
        Video::factory()->published()->provider(VideoProvider::Link, 'https://example.org/x')->create(['slug' => 'bare', 'date' => null, 'event' => null, 'location' => null, 'description' => null]);
        Video::factory()->draft()->create(['slug' => 'draft-talk']);

        $this->get(route('videos.show', 'bare'))
            ->assertOk()
            ->assertDontSee('<time', false)
            ->assertDontSee('t-label mt-6', false)
            ->assertDontSee('max-w-measure', false);

        $this->get(route('videos.show', 'draft-talk'))->assertNotFound();
        $this->get(route('videos.show', 'missing'))->assertNotFound();
    }

    #[Test]
    public function the_videos_section_is_marked_current_on_list_and_detail_pages(): void
    {
        Video::factory()->published()->create(['slug' => 'talk']);

        foreach ([route('videos'), route('videos.show', 'talk')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match_all('/<a href="([^"]+)"[^>]*aria-current="page"[^>]*>/', $html, $matches);
            $this->assertSame([route('videos')], array_values(array_unique($matches[1])));
        }
    }
}
