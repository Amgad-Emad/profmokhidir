<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Constants\Brand;
use App\Constants\SocialCards;
use GdImage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Renders the static Open Graph share cards (1200×630 PNG) once, into public/.
 * Evergreen with the soft brass glow, the MK mark in its hairline ring, the
 * brand quote in Cormorant Garamond Light Italic and the domain in label style.
 * Nothing here runs at request time.
 */
#[Signature('brand:social-cards {--output= : Directory to write into (defaults to public/)}')]
#[Description('Generate the static Open Graph share images into public/')]
class GenerateSocialCards extends Command
{
    /** Drawn at this multiple and downsampled for smooth edges. */
    private const int SUPERSAMPLE = 3;

    private const int QUOTE_SIZE_PX = 60;

    private const int QUOTE_MAX_WIDTH_PX = 980;

    private const int LABEL_SIZE_PX = 15;

    private const float LABEL_TRACKING_EM = 0.3;

    private const int RULE_WIDTH_PX = 48;

    public function handle(): int
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagettftext')) {
            $this->error('The GD extension with FreeType support is required.');

            return self::FAILURE;
        }

        $directory = rtrim((string) ($this->option('output') ?: public_path()), '/');

        foreach (SocialCards::all() as $section => $relativePath) {
            $path = $directory.'/'.$relativePath;

            if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0755, true) && ! is_dir(dirname($path))) {
                throw new RuntimeException(sprintf('Could not create %s.', dirname($path)));
            }

            $card = $this->render();

            if (! imagepng($card, $path, 9)) {
                throw new RuntimeException(sprintf('Could not write %s.', $path));
            }

            imagedestroy($card);

            $this->components->info(sprintf('%s card written to %s (%d×%d).', $section, $path, SocialCards::WIDTH, SocialCards::HEIGHT));
        }

        return self::SUCCESS;
    }

    private function render(): GdImage
    {
        $s = self::SUPERSAMPLE;
        $width = SocialCards::WIDTH * $s;
        $height = SocialCards::HEIGHT * $s;

        $canvas = $this->background($width, $height);

        $brass = $this->colour($canvas, Brand::BRASS);
        $ivory = $this->colour($canvas, Brand::IVORY);

        // Mark: hairline brass ring with ivory "MK" letters, centred near the top.
        $markSize = SocialCards::MARK_SIZE_PX * $s;
        $markCentreX = (int) ($width / 2);
        $markCentreY = 166 * $s;
        $this->drawMark($canvas, $markCentreX, $markCentreY, $markSize, $brass, $ivory);

        // Quote in the display italic, wrapped to the card's measure and centred.
        $quoteSize = self::QUOTE_SIZE_PX * $s;
        $quoteFont = $this->font(SocialCards::FONT_DISPLAY_ITALIC);
        $lines = $this->wrap(SocialCards::QUOTE, $quoteFont, $quoteSize, self::QUOTE_MAX_WIDTH_PX * $s);
        $lineHeight = (int) round($quoteSize * 1.25);
        $quoteTop = 316 * $s;

        foreach ($lines as $index => $line) {
            $this->drawCentred($canvas, $line, $quoteFont, $quoteSize, $ivory, $quoteTop + $index * $lineHeight);
        }

        // Short brass rule, then the domain in label style (uppercase, 0.3em tracking).
        $ruleY = $quoteTop + count($lines) * $lineHeight + 48 * $s;
        $ruleHalf = (int) (self::RULE_WIDTH_PX * $s / 2);
        imagefilledrectangle($canvas, $markCentreX - $ruleHalf, $ruleY, $markCentreX + $ruleHalf, $ruleY + $s - 1, $brass);

        $this->drawTracked(
            $canvas,
            mb_strtoupper(SocialCards::DOMAIN),
            $this->font(SocialCards::FONT_LABEL),
            self::LABEL_SIZE_PX * $s,
            $brass,
            $ruleY + 56 * $s,
        );

        return $this->downsample($canvas, SocialCards::WIDTH, SocialCards::HEIGHT);
    }

    /**
     * Evergreen with the soft brass radial glow from the top centre, the only permitted gradient.
     */
    private function background(int $width, int $height): GdImage
    {
        $small = imagecreatetruecolor(600, 315);
        [$er, $eg, $eb] = $this->rgb(Brand::EVERGREEN);
        [$br, $bg, $bb] = $this->rgb(Brand::BRASS);
        $radiusX = 600 * 0.70;
        $radiusY = 315 * 0.55;

        for ($y = 0; $y < 315; $y++) {
            for ($x = 0; $x < 600; $x++) {
                $dx = ($x - 300) / $radiusX;
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
        imagecopyresampled($canvas, $small, 0, 0, 0, 0, $width, $height, 600, 315);
        imagedestroy($small);

        return $canvas;
    }

    private function drawMark(GdImage $canvas, int $cx, int $cy, int $size, int $ring, int $ink): void
    {
        $s = self::SUPERSAMPLE;
        $radius = (int) ($size / 2);

        // 1px hairline at the final size: a brass disc with the evergreen-with-glow centre restored.
        $inner = imagecreatetruecolor($size, $size);
        imagecopy($inner, $canvas, 0, 0, $cx - $radius, $cy - $radius, $size, $size);
        imagefilledellipse($canvas, $cx, $cy, $size, $size, $ring);

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
                    imagesetpixel($canvas, $cx - $radius + $x, $cy - $radius + $y, imagecolorat($inner, $x, $y));
                }
            }
        }

        imagedestroy($mask);
        imagedestroy($inner);

        // Letters: Cormorant Garamond Light "MK" at 44/96 of the ring, optically centred.
        $font = $this->font(SocialCards::FONT_DISPLAY);
        $pointSize = $this->pointSizeForWidth('MK', $font, $size * 0.655);
        $box = imagettfbbox($pointSize, 0, $font, 'MK');
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = (int) round($cx - $textWidth / 2 - $box[0]);
        $y = (int) round($cy + $textHeight / 2 - $box[1]);
        imagettftext($canvas, $pointSize, 0, $x, $y, $ink, $font, 'MK');
    }

    /**
     * @return list<string>
     */
    private function wrap(string $text, string $font, float $size, int $maxWidth): array
    {
        $lines = [];
        $current = '';

        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
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

        return $lines;
    }

    private function drawCentred(GdImage $canvas, string $text, string $font, float $size, int $colour, int $top): void
    {
        $box = imagettfbbox($size, 0, $font, $text);
        $x = (int) round(imagesx($canvas) / 2 - ($box[2] - $box[0]) / 2 - $box[0]);
        $baseline = $top - $box[7];
        imagettftext($canvas, $size, 0, $x, $baseline, $colour, $font, $text);
    }

    /**
     * Draws text one glyph at a time with letter spacing, centred on the canvas.
     */
    private function drawTracked(GdImage $canvas, string $text, string $font, float $size, int $colour, int $top): void
    {
        $tracking = $size * self::LABEL_TRACKING_EM;
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $positions = [];
        $prefix = '';

        foreach ($chars as $index => $char) {
            $positions[] = $this->textWidth($prefix, $font, $size) + $index * $tracking;
            $prefix .= $char;
        }

        $totalWidth = $this->textWidth($text, $font, $size) + (count($chars) - 1) * $tracking;
        $left = imagesx($canvas) / 2 - $totalWidth / 2;
        $box = imagettfbbox($size, 0, $font, $text);
        $baseline = $top - $box[7];

        foreach ($chars as $index => $char) {
            imagettftext($canvas, $size, 0, (int) round($left + $positions[$index]), $baseline, $colour, $font, $char);
        }
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

    private function downsample(GdImage $canvas, int $width, int $height): GdImage
    {
        $card = imagecreatetruecolor($width, $height);
        imagecopyresampled($card, $canvas, 0, 0, 0, 0, $width, $height, imagesx($canvas), imagesy($canvas));
        imagedestroy($canvas);

        return $card;
    }

    private function font(string $file): string
    {
        $path = resource_path('fonts/'.$file);

        if (! is_file($path)) {
            throw new RuntimeException(sprintf('Font %s is missing from resources/fonts.', $file));
        }

        return $path;
    }

    private function colour(GdImage $image, string $hex): int
    {
        [$r, $g, $b] = $this->rgb($hex);

        return imagecolorallocate($image, $r, $g, $b);
    }

    /**
     * @return array{int, int, int}
     */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
