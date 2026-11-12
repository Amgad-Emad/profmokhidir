<?php

namespace Tests\Feature\Components;

use App\Constants\BrandAssets;
use App\Constants\BrandMark;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BrandMarkTest extends TestCase
{
    #[Test]
    public function at_32px_and_above_it_is_the_full_mark_artwork(): void
    {
        $html = Blade::render('<x-brand.mark :size="48" />');

        $this->assertStringContainsString('class="brand-mark brand-mark--full"', $html);
        $this->assertStringContainsString('data-variant="full"', $html);
        $this->assertStringContainsString('src="'.asset(BrandAssets::path(BrandAssets::MARK, BrandAssets::ON_DARK)).'"', $html);
        $this->assertStringContainsString('width="'.BrandAssets::widthFor(48, BrandAssets::MARK_RATIO).'" height="48"', $html);
        $this->assertStringNotContainsString('filter', $html);
        $this->assertStringNotContainsString('rotate', $html);
    }

    #[Test]
    public function clear_space_is_one_sixth_of_the_height_on_every_side(): void
    {
        $this->assertStringContainsString('style="padding: 8px; --brand-mark-height: 48px"', Blade::render('<x-brand.mark :size="48" />'));
        $this->assertStringContainsString('style="padding: 16px; --brand-mark-height: 96px"', Blade::render('<x-brand.mark :size="96" />'));
        $this->assertStringContainsString('style="padding: 4px; --brand-mark-height: 24px"', Blade::render('<x-brand.mark :size="24" />'));
    }

    #[Test]
    public function below_32px_it_becomes_the_square_evergreen_icon(): void
    {
        $html = Blade::render('<x-brand.mark :size="24" tone="evergreen" />');

        $this->assertStringContainsString('brand-mark--icon', $html);
        $this->assertStringContainsString('data-variant="icon"', $html);
        $this->assertStringContainsString('src="'.asset(BrandAssets::ICON).'" width="24" height="24"', $html);
        $this->assertSame(1, substr_count($html, '<img'), 'The icon is the same in both modes');

        $this->assertStringContainsString('brand-mark--full', Blade::render('<x-brand.mark :size="'.BrandMark::DISC_BELOW_PX.'" />'));
    }

    #[Test]
    public function tone_picks_the_colourway(): void
    {
        $evergreen = Blade::render('<x-brand.mark :size="64" tone="evergreen" />');
        $this->assertStringContainsString('src="'.asset(BrandAssets::path(BrandAssets::MARK, BrandAssets::ON_LIGHT)).'"', $evergreen);
        $this->assertStringContainsString('brand-art--light', $evergreen);
        $this->assertStringContainsString('src="'.asset(BrandAssets::path(BrandAssets::MARK, BrandAssets::ON_DARK)).'"', $evergreen);
        $this->assertStringContainsString('brand-art--dark', $evergreen, 'On the page surface both colourways are drawn and CSS shows the one for the mode');

        $ivory = Blade::render('<x-brand.mark :size="64" tone="ivory" />');
        $this->assertSame(1, substr_count($ivory, '<img'));
        $this->assertStringNotContainsString(BrandAssets::ON_LIGHT, $ivory);

        $this->assertStringContainsString('data-tone="ivory"', Blade::render('<x-brand.mark :size="64" tone="purple" />'), 'Unknown tones fall back to the default');
    }

    #[Test]
    public function it_has_an_accessible_name_unless_marked_decorative(): void
    {
        $html = Blade::render('<x-brand.mark />');

        $this->assertStringContainsString('role="img" aria-label="'.BrandMark::TITLE.'"', $html);
        $this->assertStringContainsString('alt=""', $html, 'The image inside is named by its wrapper');

        $decorative = Blade::render('<x-brand.mark decorative />');
        $this->assertStringContainsString('aria-hidden="true"', $decorative);
        $this->assertStringNotContainsString('role="img"', $decorative);
    }

    #[Test]
    public function extra_attributes_merge_onto_the_wrapper(): void
    {
        $this->assertStringContainsString('class="brand-mark brand-mark--full shrink-0"', Blade::render('<x-brand.mark class="shrink-0" />'));
    }

    #[Test]
    public function every_artwork_it_draws_exists(): void
    {
        $this->assertFileExists(public_path(BrandAssets::ICON));

        foreach (BrandAssets::colourways() as $colourway) {
            foreach ([BrandAssets::MARK, BrandAssets::LOGO_HORIZONTAL, BrandAssets::LOGO_COMPACT, BrandAssets::LOGO_STACKED, BrandAssets::LOGO_STACKED_NO_TAGLINE] as $artwork) {
                $this->assertFileExists(public_path(BrandAssets::path($artwork, $colourway)));
            }
        }
    }
}
