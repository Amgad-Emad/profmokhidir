<?php

namespace Tests\Feature\Components;

use App\Constants\BrandAudit;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SkeletonTest extends TestCase
{
    #[Test]
    public function it_renders_linen_lines_hidden_from_assistive_technology(): void
    {
        $html = Blade::render('<x-skeleton />');

        $this->assertStringContainsString('<div class="skeleton" aria-hidden="true" data-skeleton>', $html);
        $this->assertSame(3, substr_count($html, '<span class="skeleton__line"'));
        $this->assertStringContainsString('style="--w: 55%"', $html, 'The first line is shorter, like a heading');
        $this->assertStringContainsString('style="--w: 70%"', $html, 'The last line ends early, like a paragraph');
    }

    #[Test]
    public function lines_are_clamped_and_the_ivory_tone_is_for_evergreen_surfaces(): void
    {
        $this->assertSame(8, substr_count(Blade::render('<x-skeleton :lines="20" />'), '<span class="skeleton__line"'));
        $this->assertSame(1, substr_count(Blade::render('<x-skeleton :lines="0" />'), '<span class="skeleton__line"'));
        $this->assertStringContainsString('class="skeleton skeleton--ivory mt-4"', Blade::render('<x-skeleton tone="ivory" class="mt-4" />'));
    }

    #[Test]
    public function the_breath_is_slow_in_linen_and_permitted_by_the_brand_audit(): void
    {
        $css = file_get_contents(resource_path('css/wayfinding.css'));

        $this->assertMatchesRegularExpression('/\.skeleton__line \{[^}]*background-color: var\(--surface-raised\);[^}]*animation: skeleton-breathe 2400ms ease-in-out infinite alternate;/s', $css);
        $this->assertContains('skeleton-breathe', BrandAudit::permittedKeyframes());
    }
}
