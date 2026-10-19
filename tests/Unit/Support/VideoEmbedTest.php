<?php

namespace Tests\Unit\Support;

use App\Constants\VideoProvider;
use App\Support\VideoEmbed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class VideoEmbedTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function youtubeLinks(): iterable
    {
        yield 'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10s'];
        yield 'short link' => ['https://youtu.be/dQw4w9WgXcQ?si=abc'];
        yield 'embed' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'];
        yield 'shorts' => ['https://m.youtube.com/shorts/dQw4w9WgXcQ'];
        yield 'live' => ['https://youtube.com/live/dQw4w9WgXcQ'];
    }

    #[Test]
    #[DataProvider('youtubeLinks')]
    public function youtube_links_become_cookie_free_embeds_that_only_start_on_play(string $url): void
    {
        $this->assertSame('dQw4w9WgXcQ', VideoEmbed::youtubeId($url));
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0&modestbranding=1',
            VideoEmbed::embedUrl(VideoProvider::YouTube, $url),
        );
    }

    #[Test]
    public function vimeo_links_become_do_not_track_embeds_and_keep_unlisted_hashes(): void
    {
        $this->assertSame('https://player.vimeo.com/video/76979871?dnt=1&autoplay=1', VideoEmbed::embedUrl(VideoProvider::Vimeo, 'https://vimeo.com/76979871'));
        $this->assertSame('https://player.vimeo.com/video/76979871?dnt=1&autoplay=1', VideoEmbed::vimeo('https://player.vimeo.com/video/76979871?title=0'));
        $this->assertSame('https://player.vimeo.com/video/76979871?dnt=1&autoplay=1', VideoEmbed::vimeo('https://vimeo.com/channels/staffpicks/76979871'));
        $this->assertSame('https://player.vimeo.com/video/76979871?dnt=1&autoplay=1&h=abcdef1234', VideoEmbed::vimeo('https://vimeo.com/76979871/abcdef1234'));
        $this->assertSame('https://player.vimeo.com/video/76979871?dnt=1&autoplay=1&h=abcdef1234', VideoEmbed::vimeo('https://vimeo.com/76979871?h=abcdef1234'));
        $this->assertSame(['76979871', null], VideoEmbed::vimeoId('https://www.vimeo.com/video/76979871'));
    }

    #[Test]
    public function unrecognised_links_and_other_providers_yield_nothing(): void
    {
        $this->assertNull(VideoEmbed::youtubeId(null));
        $this->assertNull(VideoEmbed::youtubeId(''));
        $this->assertNull(VideoEmbed::youtubeId('https://www.youtube.com/channel/UC123'));
        $this->assertNull(VideoEmbed::youtubeId('https://example.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(VideoEmbed::youtubeId('javascript:alert(1)'));
        $this->assertNull(VideoEmbed::youtubeId('https://www.youtube.com/watch?v=<script>'));
        $this->assertNull(VideoEmbed::vimeoId('https://vimeo.com/about'));
        $this->assertNull(VideoEmbed::vimeoId('https://example.com/76979871'));
        $this->assertNull(VideoEmbed::embedUrl(VideoProvider::YouTube, 'https://vimeo.com/76979871'));
        $this->assertNull(VideoEmbed::embedUrl(VideoProvider::Vimeo, 'https://youtu.be/dQw4w9WgXcQ'));
        $this->assertNull(VideoEmbed::embedUrl(VideoProvider::Link, 'https://youtu.be/dQw4w9WgXcQ'));
        $this->assertNull(VideoEmbed::embedUrl(VideoProvider::File, null));
    }
}
