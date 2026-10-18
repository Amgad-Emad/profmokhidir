<?php

namespace Tests\Feature\Components;

use App\Constants\Brand;
use App\Constants\BrandMark;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BrandMarkTest extends TestCase
{
    #[Test]
    public function at_32px_and_above_it_is_a_hairline_brass_ring_that_never_scales(): void
    {
        $svg = Blade::render('<x-brand.mark :size="48" />');

        $this->assertStringContainsString('class="brand-mark brand-mark--ring"', $svg);
        $this->assertStringContainsString('width="48" height="48"', $svg);
        $this->assertStringContainsString('viewBox="0 0 96 96"', $svg);
        $this->assertMatchesRegularExpression('/<circle[^>]*fill="none"[^>]*stroke-width="1"[^>]*vector-effect="non-scaling-stroke"[^>]*stroke: var\(--color-brass, '.Brand::BRASS.'\)/', $svg);
        $this->assertStringContainsString('<path d="'.BrandMark::LETTERS_PATH.'" style="fill: var(--color-ivory, '.Brand::IVORY.')"', $svg);
        $this->assertStringNotContainsString('filter', $svg);
        $this->assertStringNotContainsString('rotate', $svg);
    }

    #[Test]
    public function clear_space_is_one_sixth_of_the_diameter_on_every_side(): void
    {
        $this->assertStringContainsString('style="padding: 8px"', Blade::render('<x-brand.mark :size="48" />'));
        $this->assertStringContainsString('style="padding: 16px"', Blade::render('<x-brand.mark :size="96" />'));
        $this->assertStringContainsString('style="padding: 4px"', Blade::render('<x-brand.mark :size="24" />'));
    }

    #[Test]
    public function below_32px_it_becomes_a_solid_evergreen_disc_with_ivory_letters(): void
    {
        $svg = Blade::render('<x-brand.mark :size="24" tone="evergreen" />');

        $this->assertStringContainsString('brand-mark--disc', $svg);
        $this->assertStringContainsString('data-variant="disc"', $svg);
        $this->assertMatchesRegularExpression('/<circle cx="48" cy="48" r="48" style="fill: var\(--color-evergreen, '.Brand::EVERGREEN.'\)"/', $svg);
        $this->assertStringNotContainsString('stroke', $svg);
        $this->assertStringContainsString('fill: var(--color-ivory', $svg, 'Letters stay ivory even when an evergreen tone is requested');

        $this->assertStringContainsString('brand-mark--ring', Blade::render('<x-brand.mark :size="32" />'));
    }

    #[Test]
    public function tone_sets_the_ink_of_the_letters(): void
    {
        $this->assertStringContainsString('fill: var(--color-evergreen, '.Brand::EVERGREEN.')"', Blade::render('<x-brand.mark :size="64" tone="evergreen" />'));
        $this->assertStringContainsString('fill: var(--color-ivory, '.Brand::IVORY.')"', Blade::render('<x-brand.mark :size="64" tone="ivory" />'));
        $this->assertStringContainsString('data-tone="ivory"', Blade::render('<x-brand.mark :size="64" tone="purple" />'), 'Unknown tones fall back to the default');
    }

    #[Test]
    public function it_has_an_accessible_title_unless_marked_decorative(): void
    {
        $svg = Blade::render('<x-brand.mark />');

        preg_match('/aria-labelledby="([^"]+)"/', $svg, $labelledBy);
        $this->assertStringContainsString('role="img"', $svg);
        $this->assertStringContainsString('<title id="'.$labelledBy[1].'">'.BrandMark::TITLE.'</title>', $svg);

        $decorative = Blade::render('<x-brand.mark decorative />');
        $this->assertStringContainsString('aria-hidden="true"', $decorative);
        $this->assertStringNotContainsString('role="img"', $decorative);
    }

    #[Test]
    public function extra_attributes_merge_onto_the_svg(): void
    {
        $this->assertStringContainsString('class="brand-mark brand-mark--ring shrink-0"', Blade::render('<x-brand.mark class="shrink-0" />'));
    }
}
