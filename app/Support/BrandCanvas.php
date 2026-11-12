<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\Brand;
use App\Constants\BrandAssets;
use App\Constants\SocialCards;
use ArPHP\I18N\Arabic;
use GdImage;
use RuntimeException;

/**
 * A GD canvas that draws in the brand: evergreen with the soft brass radial
 * glow, the MK mark in its hairline ring, Cormorant Garamond and Hanken
 * Grotesk from resources/fonts, centred and letter-spaced text. Everything is
 * drawn at a multiple of the final size and downsampled for smooth edges.
 * Shared by the Open Graph cards (brand:social-cards) and the tribute share cards.
 *
 * Arabic: GD neither joins Arabic letters nor lays them out right to left, so
 * any text containing Arabic is shaped first (ar-php converts it to its
 * presentation forms in visual order) and set in Amiri (SocialCards::FONT_ARABIC*)
 * in place of the Latin brand fonts. Arabic is never letter-spaced, since
 * tracking would break the joins. Lines are wrapped in logical order and
 * shaped one at a time, so each line reads right to left on its own.
 */
final class BrandCanvas
{
    /** Drawn at this multiple and downsampled for smooth edges. */
    public const int SUPERSAMPLE = 3;

    /** Arabic drawn through tracked() (labels) is set this much larger than the Latin size asked for. */
    public const float ARABIC_LABEL_SCALE = 1.3;

    /** Width of the mark artwork as a share of the square passed to mark(). */
    public const float MARK_WIDTH_RATIO = 1.4;

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
     * The mark (book, sprout and rays, the on-dark artwork in BrandAssets::MARK_PNG_ON_DARK),
     * centred on (cx, cy) in final-size pixels. `size` is the square it fits in: the mark is
     * MARK_WIDTH_RATIO of it wide and keeps its own proportions, so it never exceeds the height.
     */
    public function mark(int $cx, int $cy, int $size): void
    {
        $file = public_path(BrandAssets::MARK_PNG_ON_DARK);
        $art = is_file($file) ? imagecreatefrompng($file) : false;

        if ($art === false) {
            throw new RuntimeException("Brand mark artwork not found: {$file}");
        }

        $width = $this->px((int) round($size * self::MARK_WIDTH_RATIO));
        $height = (int) round($width * imagesy($art) / imagesx($art));

        imagealphablending($this->canvas, true);
        imagecopyresampled($this->canvas, $art, $this->px($cx) - intdiv($width, 2), $this->px($cy) - intdiv($height, 2), 0, 0, $width, $height, imagesx($art), imagesy($art));
        imagedestroy($art);
    }

    /**
     * Break text into lines no wider than `maxWidth` (final-size pixels) at `size` (final-size pixels).
     *
     * @return list<string>
     */
    public function wrap(string $text, string $fontFile, int $size, int $maxWidth): array
    {
        $font = self::font(self::fontFor($fontFile, $text));
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

                if ($current !== '' && $this->textWidth(self::visual($candidate), $font, $size) > $maxWidth) {
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
        $font = self::font(self::fontFor($fontFile, $text));
        $text = self::visual($text);
        $size = $this->px($size);
        $box = imagettfbbox($size, 0, $font, $text);
        $x = (int) round($this->width() / 2 - ($box[2] - $box[0]) / 2 - $box[0]);
        imagettftext($this->canvas, $size, 0, $x, $this->px($top) - $box[7], $this->colour($hex), $font, $text);
    }

    /**
     * Draw a label line made of separate parts (relationship, place) when any of
     * them is Arabic: each part is shaped on its own and the parts are laid out
     * right to left on Arabic pages (`rtl`), left to right otherwise, so a Latin
     * place beside an Arabic label (or the reverse) never scrambles. Untracked,
     * in Amiri bold at ARABIC_LABEL_SCALE. Without Arabic, falls back to tracked().
     *
     * @param  list<string>  $parts
     */
    public function labelRuns(array $parts, string $separator, int $size, string $hex, int $top, float $tracking, bool $rtl): void
    {
        $parts = array_values(array_filter($parts, fn (string $part): bool => $part !== ''));

        if (! self::isArabic(implode('', $parts))) {
            $this->tracked(mb_strtoupper(implode($separator, $parts)), SocialCards::FONT_LABEL, $size, $hex, $top, $tracking);

            return;
        }

        $runs = array_map(fn (string $part): string => self::isArabic($part) ? self::visual($part) : mb_strtoupper($part), $parts);
        $text = implode($separator, $rtl ? array_reverse($runs) : $runs);

        $font = self::font(SocialCards::FONT_ARABIC_BOLD);
        $size = $this->px((int) round($size * self::ARABIC_LABEL_SCALE));
        $box = imagettfbbox($size, 0, $font, $text);
        $x = (int) round($this->width() / 2 - ($box[2] - $box[0]) / 2 - $box[0]);
        imagettftext($this->canvas, $size, 0, $x, $this->px($top) - $box[7], $this->colour($hex), $font, $text);
    }

    /**
     * Draw text one glyph at a time with letter spacing (`tracking` in em), centred horizontally.
     */
    public function tracked(string $text, string $fontFile, int $size, string $hex, int $top, float $tracking): void
    {
        if (self::isArabic($text)) {
            // Untracked, and a little larger: Amiri's small sizes read smaller than Hanken's.
            $this->centred($text, $fontFile, (int) round($size * self::ARABIC_LABEL_SCALE), $hex, $top);

            return;
        }

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
     * Whether the text contains Arabic letters (and so must be shaped and set in Amiri).
     */
    public static function isArabic(string $text): bool
    {
        return preg_match('/\p{Arabic}/u', $text) === 1;
    }

    /**
     * The font a run of text is drawn in: the brand font asked for, or Amiri
     * (bold for the label face) when the text contains Arabic.
     */
    public static function fontFor(string $fontFile, string $text): string
    {
        if (! self::isArabic($text)) {
            return $fontFile;
        }

        return $fontFile === SocialCards::FONT_LABEL ? SocialCards::FONT_ARABIC_BOLD : SocialCards::FONT_ARABIC;
    }

    /**
     * One line of text in the order GD draws it: Arabic is joined (presentation
     * forms) and laid out right to left, with any Latin words and figures kept in
     * reading order inside it. Text without Arabic is returned unchanged.
     */
    public static function visual(string $line): string
    {
        if (! self::isArabic($line)) {
            return $line;
        }

        static $shaper = null;
        $shaper ??= new Arabic;

        // Vowel marks (tashkeel) are dropped: GD cannot position them over the joined forms.
        $line = (string) preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $line);

        return $shaper->utf8Glyphs($line, PHP_INT_MAX, false, true);
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
