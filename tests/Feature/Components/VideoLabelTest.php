<?php

namespace Tests\Feature\Components;

use App\Models\Video;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VideoLabelTest extends TestCase
{
    #[Test]
    public function it_writes_event_place_and_date_in_that_order(): void
    {
        $video = Video::factory()->make(['event' => 'Inaugural Lecture', 'location' => 'Khartoum', 'date' => '1983-03-12']);
        $html = Blade::render('<x-video-label :video="$video" class="mt-6" />', ['video' => $video]);

        $this->assertStringContainsString('<p class="t-label text-bronze mt-6">', $html);
        $this->assertMatchesRegularExpression('/Inaugural Lecture\s*· Khartoum\s*· <time datetime="1983-03-12">12 March 1983<\/time>/', $html);
        $this->assertStringContainsString('text-brass', Blade::render('<x-video-label :video="$video" tone="ivory" />', ['video' => $video]));
    }

    #[Test]
    public function it_omits_missing_parts_and_renders_nothing_without_any(): void
    {
        $html = Blade::render('<x-video-label :video="$video" />', ['video' => Video::factory()->make(['event' => null, 'location' => 'Khartoum', 'date' => null])]);

        $this->assertMatchesRegularExpression('/<p class="t-label text-bronze">\s*Khartoum\s*<\/p>/', $html);
        $this->assertStringNotContainsString('·', $html);

        $html = Blade::render('<x-video-label :video="$video" />', ['video' => Video::factory()->make(['event' => null, 'location' => null, 'date' => '1983-03-12'])]);
        $this->assertMatchesRegularExpression('/<p class="t-label text-bronze">\s*<time datetime="1983-03-12">12 March 1983<\/time>\s*<\/p>/', $html);

        $this->assertSame('', trim(Blade::render('<x-video-label :video="$video" />', ['video' => Video::factory()->make(['event' => null, 'location' => ' ', 'date' => null])])));
    }
}
