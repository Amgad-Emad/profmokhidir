<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Colour arithmetic for the theme palette, on hex strings: parsing #rgb,
 * #rrggbb and #rrggbbaa, CSS color-mix() in sRGB (premultiplied alpha, as the
 * browser computes it), compositing a translucent colour over an opaque one,
 * WCAG 2.2 relative luminance and contrast, and the nearest shade of a colour
 * that reaches a contrast ratio. Values come back as lower-case #rrggbb, or
 * #rrggbbaa when translucent.
 */
final class Colour
{
    public const string PATTERN = '/^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i';

    public const string WHITE = '#ffffff';

    public const string BLACK = '#000000';

    /** @var array<string, array{float, float, float, float}> */
    private static array $parsed = [];

    /** @var array<string, float> */
    private static array $contrasts = [];

    public static function isValid(mixed $value): bool
    {
        return is_string($value) && preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * #rgb, #rrggbb or #rrggbbaa as lower-case #rrggbb (or #rrggbbaa below full opacity).
     */
    public static function normalise(string $hex): string
    {
        return self::hex(self::parse($hex));
    }

    /**
     * @return array{float, float, float, float} red, green, blue and alpha, each 0..1
     */
    public static function parse(string $hex): array
    {
        if (isset(self::$parsed[$hex])) {
            return self::$parsed[$hex];
        }

        if (! self::isValid($hex)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a hex colour.', $hex));
        }

        $digits = strtolower(substr($hex, 1));

        if (strlen($digits) === 3) {
            $digits = $digits[0].$digits[0].$digits[1].$digits[1].$digits[2].$digits[2];
        }

        $channel = static fn (int $offset): float => hexdec(substr($digits, $offset, 2)) / 255;

        if (count(self::$parsed) > 4096) {
            self::$parsed = [];
        }

        return self::$parsed[$hex] = [$channel(0), $channel(2), $channel(4), strlen($digits) === 8 ? $channel(6) : 1.0];
    }

    /**
     * @param  array{float, float, float, float}  $rgba
     */
    public static function hex(array $rgba): string
    {
        $byte = static fn (float $value): int => (int) round(max(0, min(1, $value)) * 255);
        $hex = sprintf('#%02x%02x%02x', $byte($rgba[0]), $byte($rgba[1]), $byte($rgba[2]));

        return $byte($rgba[3]) < 255 ? $hex.sprintf('%02x', $byte($rgba[3])) : $hex;
    }

    /**
     * color-mix(in srgb, $a $percent%, $b): the share of $b is what is left.
     */
    public static function mix(string $a, float $percent, string $b): string
    {
        $first = self::parse($a);
        $second = self::parse($b);
        $shareA = $percent / 100;
        $shareB = 1 - $shareA;
        $alpha = $first[3] * $shareA + $second[3] * $shareB;

        if ($alpha <= 0) {
            return '#00000000';
        }

        $channel = static fn (int $i): float => ($first[$i] * $first[3] * $shareA + $second[$i] * $second[3] * $shareB) / $alpha;

        return self::hex([$channel(0), $channel(1), $channel(2), $alpha]);
    }

    /**
     * color-mix(in srgb, $colour $percent%, transparent).
     */
    public static function alpha(string $colour, float $percent): string
    {
        [$r, $g, $b, $a] = self::parse($colour);

        return self::hex([$r, $g, $b, $a * $percent / 100]);
    }

    /**
     * The colour without its alpha.
     */
    public static function opaque(string $colour): string
    {
        [$r, $g, $b] = self::parse($colour);

        return self::hex([$r, $g, $b, 1.0]);
    }

    public static function isTranslucent(string $colour): bool
    {
        return self::parse($colour)[3] < 1;
    }

    /**
     * A translucent colour laid over an opaque background, as the browser paints it.
     */
    public static function over(string $foreground, string $background): string
    {
        $fg = self::parse($foreground);
        $bg = self::parse($background);
        $bg = $bg[3] < 1 ? self::parse(self::over($background, self::WHITE)) : $bg;
        $a = $fg[3];

        return self::hex([
            $fg[0] * $a + $bg[0] * (1 - $a),
            $fg[1] * $a + $bg[1] * (1 - $a),
            $fg[2] * $a + $bg[2] * (1 - $a),
            1.0,
        ]);
    }

    /**
     * Lay a stack of colours one over the other, the first at the bottom.
     *
     * @param  list<string>  $layers
     */
    public static function flatten(array $layers): string
    {
        $result = self::WHITE;

        foreach ($layers as $layer) {
            $result = self::over($layer, $result);
        }

        return $result;
    }

    public static function luminance(string $colour): float
    {
        [$r, $g, $b] = self::parse($colour);
        $linear = static fn (float $c): float => $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $linear($r) + 0.7152 * $linear($g) + 0.0722 * $linear($b);
    }

    public static function isDark(string $colour): bool
    {
        return self::luminance(self::over($colour, self::WHITE)) < 0.18;
    }

    /**
     * WCAG contrast of an ink (composited first when translucent) over a background.
     */
    public static function contrast(string $ink, string $background): float
    {
        $key = $ink.'|'.$background;

        if (isset(self::$contrasts[$key])) {
            return self::$contrasts[$key];
        }

        if (count(self::$contrasts) > 8192) {
            self::$contrasts = [];
        }

        return self::$contrasts[$key] = self::computeContrast($ink, $background);
    }

    private static function computeContrast(string $ink, string $background): float
    {
        $background = self::over($background, self::WHITE);
        $ink = self::over($ink, $background);
        $l1 = self::luminance($ink);
        $l2 = self::luminance($background);

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    /**
     * The colour itself when it reaches $ratio over the background, else the
     * nearest shade that does: mixed toward black or white one percent at a
     * time, whichever direction gets there with the smaller change. Alpha is kept.
     */
    public static function ensure(string $ink, string $background, float $ratio): string
    {
        if (self::contrast($ink, $background) >= $ratio) {
            return self::normalise($ink);
        }

        $alpha = self::parse($ink)[3];
        $candidates = [];

        foreach ([self::BLACK, self::WHITE] as $towards) {
            for ($step = 1; $step <= 100; $step++) {
                $shade = self::mix(self::opaque($ink), 100 - $step, $towards);
                $shade = $alpha < 1 ? self::alpha($shade, $alpha * 100) : $shade;

                if (self::contrast($shade, $background) >= $ratio) {
                    $candidates[$step] ??= $shade;

                    break;
                }
            }
        }

        if ($candidates === []) {
            return self::contrast(self::BLACK, $background) >= self::contrast(self::WHITE, $background) ? self::BLACK : self::WHITE;
        }

        ksort($candidates);

        return reset($candidates);
    }
}
