<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\Brand;
use App\Constants\SocialCards;
use GdImage;
use RuntimeException;

/**
 * A GD canvas that draws in the brand: evergreen with the soft brass radial
 * glow, the MK mark in its hairline ring, Cormorant Garamond and Hanken
 * Grotesk from resources/fonts, centred and letter-spaced text. Everything is
 * drawn at a multiple of the final size and downsampled for smooth edges.
 * Shared by the Open Graph cards (brand:social-cards) and the tribute share cards.
 */
final class BrandCanvas
{
    /** Drawn at this multiple and downsampled for smooth edges. */
    public const int SUPERSAMPLE = 3;

    private GdImage $canvas;

    private function __construct(private readonly int $width, private readonly int $height, private readonly int $scale)
    {
        $this->canvas = $this->background($width * $scale, $height * $scale);
    }

    public static function supported(): bool
    {
        return function_exists('imagecreatetruecolor') && function_exists('imagettftext');
    }

    /**
     * A new canvas of the given final size, in evergreen with the brass glow.
     */
    public static function make(int $width, int $height, int $scale = self::SUPERSAMPLE): self
    {
        if (! self::supported()) {
            throw new RuntimeException('The GD extension with FreeType support is required.');
        }

        return new self($width, $height, max(1, $scale));
    }

    public function scale(): int
    {
        return $this->scale;
    }

    /** Working width, in supersampled pixels. */
    public function width(): int
    {
        return $this->width * $this->scale;
    }

    public function height(): int
    {
        return $this->height * $this->scale;
    }

    /** Convert a final-size measurement to working pixels. */
    public function px(float $value): int
    {
        return (int) round($value * $this->scale);
    }

    public function colour(string $hex): int
    {
        [$r, $g, $b] = self::rgb($hex);

        return imagecolorallocate($this->canvas, $r, $g, $b);
    }

    /**
     * The mark: a 1px hairline brass ring with the "MK" letters, centred on (cx, cy)
     * in final-size pixels; `size` is the ring diameter in final-size pixels.
     */
    public function mark(int $cx, int $cy, int $size, string $ringHex = Brand::BRASS, string $inkHex = Brand::IVORY): void
    {
        $s = $this->scale;
        $cx = $this->px($cx);
        $cy = $this->px($cy);
        $size = $this->px($size);
        $radius = (int) ($size / 2);
        $ring = $this->colour($ringHex);
        $ink = $this->colour($inkHex);

        // Hairline at the final size: a brass disc with the glowing centre restored.
        $inner = imagecreatetruecolor($size, $size);
        imagecopy($inner, $this->canvas, 0, 0, $cx - $radius, $cy - $radius, $size, $size);
        imagefilledellipse($this->canvas, $cx, $cy, $size, $size, $ring);

        $mask = imagecreatetruecolor($size, $size);
        $transparent = imagecolorallocatealpha($mask, 0, 0, 0, 127);
        imagefill($mask, 0, 0, $transparent);
        imagealphablending($mask, false);
        imagesavealpha($mask, true);
        $opaque = imagecolorallocatealpha($mask, 0, 0, 0, 0);
        imagefilledellipse($mask, $radius, $radius, $size - 2 * $s, $size - 2 * $s, $opaque);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ((imagecolorat($mask, $x, $y) >> 24) === 0) {
                    imagesetpixel($this->canvas, $cx - $radius + $x, $cy - $radius + $y, imagecolorat($inner, $x, $y));
                }
            }
        }

        imagedestroy($mask);
        imagedestroy($inner);

        // Letters: Cormorant Garamond Light "MK" at 44/96 of the ring, optically centred.
        $font = self::font(SocialCards::FONT_DISPLAY);
        $pointSize = $this->pointSizeForWidth('MK', $font, $size * 0.655);
        $box = imagettfbbox($pointSize, 0, $font, 'MK');
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        imagettftext($this->canvas, $pointSize, 0, (int) round($cx - $textWidth / 2 - $box[0]), (int) round($cy + $textHeight / 2 - $box[1]), $ink, $font, 'MK');
    }

    /**
     * Break text into lines no wider than `maxWidth` (final-size pixels) at `size` (final-size pixels).
     *
     * @return list<string>
     */
    public function wrap(string $text, string $fontFile, int $size, int $maxWidth): array
    {
        $font = self::font($fontFile);
        $size = $this->px($size);
        $maxWidth = $this->px($maxWidth);
        $lines = [];

        foreach (preg_split('/\R/u', trim($text)) ?: [] as $paragraph) {
            $current = '';

            foreach (preg_split('/\s+/u', trim($paragraph)) ?: [] as $word) {
                if ($word === '') {
                    continue;
                }

                $candidate = $current === '' ? $word : $current.' '.$word;

                if ($current !== '' && $this->textWidth($candidate, $font, $size) > $maxWidth) {
                    $lines[] = $current;
                    $current = $word;
                } else {
                    $current = $candidate;
                }
            }

            if ($current !== '') {
                $lines[] = $current;
            }
        }

        return $lines;
    }

    /**
     * Draw a line of text centred horizontally with its top at `top` (final-size pixels).
     */
    public function centred(string $text, string $fontFile, int $size, string $hex, int $top): void
    {
        $font = self::font($fontFile);
        $size = $this->px($size);
        $box = imagettfbbox($size, 0, $font, $text);
        $x = (int) round($this->width() / 2 - ($box[2] - $box[0]) / 2 - $box[0]);
        imagettftext($this->canvas, $size, 0, $x, $this->px($top) - $box[7], $this->colour($hex), $font, $text);
    }

    /**
     * Draw text one glyph at a time with letter spacing (`tracking` in em), centred horizontally.
     */
    public function tracked(string $text, string $fontFile, int $size, string $hex, int $top, float $tracking): void
    {
        $font = self::font($fontFile);
        $size = $this->px($size);
        $spacing = $size * $tracking;
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $positions = [];
        $prefix = '';

        foreach ($chars as $index => $char) {
            $positions[] = $this->textWidth($prefix, $font, $size) + $index * $spacing;
            $prefix .= $char;
        }

        $totalWidth = $this->textWidth($text, $font, $size) + (count($chars) - 1) * $spacing;
        $left = $this->width() / 2 - $totalWidth / 2;
        $box = imagettfbbox($size, 0, $font, $text);
        $baseline = $this->px($top) - $box[7];
        $colour = $this->colour($hex);

        foreach ($chars as $index => $char) {
            imagettftext($this->canvas, $size, 0, (int) round($left + $positions[$index]), $baseline, $colour, $font, $char);
        }
    }

    /**
     * A 1px horizontal brass rule of `width` final-size pixels, centred, with its top at `top`.
     */
    public function rule(int $width, int $top, string $hex = Brand::BRASS): void
    {
        $half = (int) ($this->px($width) / 2);
        $centre = (int) ($this->width() / 2);
        $y = $this->px($top);

        imagefilledrectangle($this->canvas, $centre - $half, $y, $centre + $half, $y + $this->scale - 1, $this->colour($hex));
    }

    /**
     * Write the downsampled PNG. Returns the final image for callers that want to inspect it.
     */
    public function png(string $path): void
    {
        if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true) && ! is_dir(dirname($path))) {
            throw new RuntimeException(sprintf('Could not create %s.', dirname($path)));
        }

        $card = imagecreatetruecolor($this->width, $this->height);
        imagecopyresampled($card, $this->canvas, 0, 0, 0, 0, $this->width, $this->height, $this->width(), $this->height());

        $written = imagepng($card, $path, 9);
        imagedestroy($card);

        if (! $written) {
            throw new RuntimeException(sprintf('Could not write %s.', $path));
        }
    }

    public function __destruct()
    {
        imagedestroy($this->canvas);
    }

    /**
     * Absolute path of a brand font in resources/fonts.
     */
    public static function font(string $file): string
    {
        $path = resource_path('fonts/'.$file);

        if (! is_file($path)) {
            throw new RuntimeException(sprintf('Font %s is missing from resources/fonts.', $file));
        }

        return $path;
    }

    /**
     * @return array{int, int, int}
     */
    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [(int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2))];
    }

    /**
     * Evergreen with the soft brass radial glow from the top centre, the only permitted gradient.
     */
    private function background(int $width, int $height): GdImage
    {
        $smallWidth = 600;
        $smallHeight = max(1, (int) round($smallWidth * $height / $width));
        $small = imagecreatetruecolor($smallWidth, $smallHeight);
        [$er, $eg, $eb] = self::rgb(Brand::EVERGREEN);
        [$br, $bg, $bb] = self::rgb(Brand::BRASS);
        $radiusX = $smallWidth * 0.70;
        $radiusY = $smallHeight * 0.55;

        for ($y = 0; $y < $smallHeight; $y++) {
            for ($x = 0; $x < $smallWidth; $x++) {
                $dx = ($x - $smallWidth / 2) / $radiusX;
                $dy = $y / $radiusY;
                $distance = sqrt($dx * $dx + $dy * $dy);
                $mix = max(0.0, 0.22 * (1 - $distance / 0.7));
                imagesetpixel($small, $x, $y, imagecolorallocate(
                    $small,
                    (int) round($er + ($br - $er) * $mix),
                    (int) round($eg + ($bg - $eg) * $mix),
                    (int) round($eb + ($bb - $eb) * $mix),
                ));
            }
        }

        $canvas = imagecreatetruecolor($width, $height);
        imagecopyresampled($canvas, $small, 0, 0, 0, 0, $width, $height, $smallWidth, $smallHeight);
        imagedestroy($small);

        return $canvas;
    }

    private function pointSizeForWidth(string $text, string $font, float $targetWidth): float
    {
        $probe = 100.0;

        return round($probe * $targetWidth / $this->textWidth($text, $font, $probe), 2);
    }

    private function textWidth(string $text, string $font, float $size): float
    {
        if ($text === '') {
            return 0.0;
        }

        $box = imagettfbbox($size, 0, $font, $text);

        return (float) ($box[2] - $box[0]);
    }
}
