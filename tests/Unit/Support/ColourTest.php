<?php

namespace Tests\Unit\Support;

use App\Support\Colour;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The colour arithmetic behind the theme palette: parsing, CSS color-mix() in
 * sRGB, compositing, WCAG contrast and the nearest passing shade.
 */
class ColourTest extends TestCase
{
    #[Test]
    public function it_parses_and_normalises_short_long_and_translucent_hex(): void
    {
        $this->assertSame('#aabbcc', Colour::normalise('#ABC'));
        $this->assertSame('#13221c', Colour::normalise('#13221C'));
        $this->assertSame('#13221c80', Colour::normalise('#13221C80'));
        $this->assertSame('#13221c', Colour::normalise('#13221cff'), 'full opacity drops the alpha pair');
        $this->assertFalse(Colour::isValid('red'));
        $this->assertFalse(Colour::isValid('#12345'));
        $this->assertFalse(Colour::isValid(['#ffffff']));

        $this->expectException(InvalidArgumentException::class);
        Colour::parse('url(x)');
    }

    #[Test]
    public function mix_and_alpha_match_css_color_mix_in_srgb(): void
    {
        // color-mix(in srgb, #13221c 78%, #000000): the solid button's brand hover.
        $this->assertSame('#0f1b16', Colour::mix('#13221c', 78, '#000000'));
        $this->assertSame('#808080', Colour::mix('#ffffff', 50, '#000000'));
        // color-mix(in srgb, #a8864f 35%, transparent)
        $this->assertSame('#a8864f59', Colour::alpha('#a8864f', 35));
        // Premultiplied: mixing with transparent keeps the hue and halves the alpha.
        $this->assertSame('#a8864f80', Colour::mix('#a8864f', 50, '#00000000'));
    }

    #[Test]
    public function contrast_follows_wcag_and_composites_translucent_inks(): void
    {
        $this->assertEqualsWithDelta(21.0, Colour::contrast('#000000', '#ffffff'), 0.001);
        $this->assertEqualsWithDelta(1.0, Colour::contrast('#777777', '#777777'), 0.001);
        // Brand: evergreen on ivory and brass on cypress (the reason raised surfaces swap the accent).
        $this->assertGreaterThan(14, Colour::contrast('#13221c', '#f3f1ea'));
        $this->assertLessThan(3, Colour::contrast('#a8864f', '#2f4a3e'));
        // A translucent ink is laid over its background first.
        $this->assertSame(Colour::contrast(Colour::over('#13221c80', '#f3f1ea'), '#f3f1ea'), Colour::contrast('#13221c80', '#f3f1ea'));
    }

    #[Test]
    public function ensure_returns_the_nearest_passing_shade_and_keeps_alpha(): void
    {
        $this->assertSame('#13221c', Colour::ensure('#13221c', '#f3f1ea', 4.5), 'a passing ink is unchanged');

        $shade = Colour::ensure('#a8864f', '#f3f1ea', 4.5);
        $this->assertGreaterThanOrEqual(4.5, Colour::contrast($shade, '#f3f1ea'));
        $this->assertLessThan(Colour::luminance('#a8864f'), Colour::luminance($shade), 'darker on a light page');

        $light = Colour::ensure('#a8864f', '#2f4a3e', 4.5);
        $this->assertGreaterThan(Colour::luminance('#a8864f'), Colour::luminance($light), 'lighter on a dark surface');

        $translucent = Colour::ensure('#a8864fd9', '#2f4a3e', 4.5);
        $this->assertSame('d9', substr($translucent, 7, 2));
        $this->assertGreaterThanOrEqual(4.5, Colour::contrast($translucent, '#2f4a3e'));
    }

    #[Test]
    public function flatten_lays_layers_bottom_up(): void
    {
        $this->assertSame('#f3f1ea', Colour::flatten(['#f3f1ea']));
        $this->assertSame(Colour::over('#13221ce0', '#f3f1ea'), Colour::flatten(['#f3f1ea', '#13221ce0']));
        $this->assertTrue(Colour::isDark('#13221c'));
        $this->assertFalse(Colour::isDark('#f3f1ea'));
    }
}
