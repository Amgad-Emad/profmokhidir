<?php

namespace Tests\Unit\Constants;

use App\Constants\BrandAssets;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BrandAssetsTest extends TestCase
{
    #[Test]
    public function path_fills_in_the_colourway_and_falls_back_to_dark(): void
    {
        $this->assertSame('images/brand/mark-on-light.svg', BrandAssets::path(BrandAssets::MARK, BrandAssets::ON_LIGHT));
        $this->assertSame('images/brand/logo-horizontal-on-dark.svg', BrandAssets::path(BrandAssets::LOGO_HORIZONTAL));
        $this->assertSame('images/brand/logo-compact-on-dark.svg', BrandAssets::path(BrandAssets::LOGO_COMPACT, 'purple'));
        $this->assertSame(BrandAssets::ICON, BrandAssets::path(BrandAssets::ICON), 'A path without a colourway passes through');
    }

    #[Test]
    public function width_follows_the_artwork_ratio(): void
    {
        $this->assertSame(278, BrandAssets::widthFor(48, BrandAssets::LOGO_HORIZONTAL_RATIO));
        $this->assertSame(164, BrandAssets::widthFor(40, BrandAssets::LOGO_COMPACT_RATIO));
        $this->assertSame(24, BrandAssets::widthFor(24, BrandAssets::ICON_RATIO));
    }

    #[Test]
    public function every_listed_file_ships_in_public(): void
    {
        $public = dirname(__DIR__, 3).'/public/';
        $files = [BrandAssets::ICON, BrandAssets::MARK_PNG_ON_DARK, BrandAssets::EMAIL_LOGO, BrandAssets::FAVICON_ICO, BrandAssets::FAVICON_SVG, BrandAssets::FAVICON_32, BrandAssets::FAVICON_16, BrandAssets::APPLE_TOUCH_ICON, BrandAssets::SAFARI_PINNED_TAB, BrandAssets::MANIFEST];

        foreach ([BrandAssets::PLACEHOLDER_AVATAR, BrandAssets::PLACEHOLDER_IMAGE_16X9, BrandAssets::PLACEHOLDER_IMAGE_4X3, BrandAssets::PLACEHOLDER_IMAGE_1X1, BrandAssets::PLACEHOLDER_PORTRAIT, BrandAssets::PLACEHOLDER_VIDEO, BrandAssets::PLACEHOLDER_PUBLICATION_COVER] as $placeholder) {
            $files[] = sprintf($placeholder, 'light');
            $files[] = sprintf($placeholder, 'dark');
        }

        foreach ($files as $file) {
            $this->assertFileExists($public.$file);
        }
    }
}
