<?php

namespace Tests\Unit\Support;

use App\Constants\Brand;
use App\Constants\SocialCards;
use App\Support\BrandCanvas;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BrandCanvasTest extends TestCase
{
    #[Test]
    public function it_draws_in_the_brand_and_writes_a_png_at_the_final_size(): void
    {
        $this->assertTrue(BrandCanvas::supported());

        $canvas = BrandCanvas::make(300, 200, 2);

        $this->assertSame(2, $canvas->scale());
        $this->assertSame([600, 400], [$canvas->width(), $canvas->height()]);
        $this->assertSame(24, $canvas->px(12));

        $canvas->mark(150, 60, 48);
        $canvas->centred('Knowledge', SocialCards::FONT_DISPLAY_ITALIC, 24, Brand::IVORY, 110);
        $canvas->rule(48, 150);
        $canvas->tracked('PROFMOKHIDIR.COM', SocialCards::FONT_LABEL, 10, Brand::BRASS, 170, 0.3);

        $path = storage_path('framework/testing/brand-canvas-'.uniqid().'.png');

        try {
            $canvas->png($path);

            $size = getimagesize($path);
            $this->assertSame([300, 200, 'image/png'], [$size[0], $size[1], $size['mime']]);

            // The corners stay evergreen: the glow only warms the top centre.
            $image = imagecreatefrompng($path);
            $corner = imagecolorsforindex($image, imagecolorat($image, 2, 197));
            $this->assertSame(BrandCanvas::rgb(Brand::EVERGREEN), [$corner['red'], $corner['green'], $corner['blue']]);
            imagedestroy($image);
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function wrap_breaks_on_the_measure_and_on_line_breaks(): void
    {
        $canvas = BrandCanvas::make(400, 100, 1);

        $lines = $canvas->wrap("one two three four five six seven eight nine ten\nnew paragraph", SocialCards::FONT_DISPLAY_ITALIC, 24, 200);

        $this->assertGreaterThan(2, count($lines));
        $this->assertSame('new paragraph', end($lines));
        $this->assertSame([], $canvas->wrap('   ', SocialCards::FONT_DISPLAY_ITALIC, 24, 200));
    }

    #[Test]
    public function fonts_and_colours_resolve(): void
    {
        $this->assertFileExists(BrandCanvas::font(SocialCards::FONT_DISPLAY));
        $this->assertSame([19, 34, 28], BrandCanvas::rgb(Brand::EVERGREEN));
        $this->assertSame([168, 134, 79], BrandCanvas::rgb('A8864F'));

        $this->expectException(\RuntimeException::class);
        BrandCanvas::font('missing.ttf');
    }
}
