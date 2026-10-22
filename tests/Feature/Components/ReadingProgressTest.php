<?php

namespace Tests\Feature\Components;

use App\Models\Commentary;
use App\Models\CvEntry;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReadingProgressTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renders_the_decorative_line_bound_to_its_target(): void
    {
        $this->assertSame(
            '<div class="reading-progress" x-data="readingProgress" data-target="#essay-body" aria-hidden="true"></div>',
            trim(Blade::render('<x-reading-progress target="#essay-body" />')),
        );
    }

    #[Test]
    public function the_long_pages_carry_it_under_the_header(): void
    {
        Profile::factory()->create();
        CvEntry::factory()->published()->create();
        Commentary::factory()->published()->create(['slug' => 'long-read']);

        foreach (['#about-body' => route('about'), '#cv-body' => route('cv'), '#essay-body' => route('commentary.show', 'long-read')] as $target => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('<div class="reading-progress" x-data="readingProgress" data-target="'.$target.'" aria-hidden="true"></div>', $html);
            $this->assertStringContainsString(' id="'.ltrim($target, '#').'"', $html);
        }

        $this->assertStringNotContainsString('class="reading-progress"', $this->get(route('awards'))->getContent());
    }

    #[Test]
    public function the_line_sits_under_the_header_and_follows_it_as_it_condenses(): void
    {
        $css = file_get_contents(resource_path('css/archive.css'));

        $this->assertMatchesRegularExpression('/\.reading-progress \{[^}]*top: var\(--header-height, 5rem\);[^}]*transition: top var\(--duration-masthead, 400ms\) var\(--ease-rise\);[^}]*background-color: var\(--color-brass\);[^}]*transform: scaleX\(var\(--reading-progress, 0\)\);/s', $css);
    }
}
