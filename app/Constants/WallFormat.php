<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * A photograph's place on the exhibition wall, chosen from its proportions so
 * it is never cropped: the frame always keeps the image's own aspect ratio.
 */
enum WallFormat: string
{
    use HasValues;

    case Portrait = 'portrait';
    case Square = 'square';
    case Landscape = 'landscape';
    case Panorama = 'panorama';

    /** Width / height below which a photo is a portrait. */
    public const float PORTRAIT_BELOW = 0.85;

    /** Width / height from which a photo is a landscape. */
    public const float LANDSCAPE_FROM = 1.2;

    /** Width / height from which a photo is a panorama. */
    public const float PANORAMA_FROM = 1.75;

    public function label(): string
    {
        return match ($this) {
            self::Portrait => 'Portrait',
            self::Square => 'Square',
            self::Landscape => 'Landscape',
            self::Panorama => 'Panorama',
        };
    }

    /**
     * Unknown proportions hang as a landscape, the most common archive print.
     */
    public static function fromDimensions(?int $width, ?int $height): self
    {
        if ($width === null || $height === null || $width <= 0 || $height <= 0) {
            return self::Landscape;
        }

        $ratio = $width / $height;

        return match (true) {
            $ratio < self::PORTRAIT_BELOW => self::Portrait,
            $ratio < self::LANDSCAPE_FROM => self::Square,
            $ratio < self::PANORAMA_FROM => self::Landscape,
            default => self::Panorama,
        };
    }

    /**
     * Columns of the 12-column wall; a featured photo hangs one size larger.
     */
    public function span(bool $featured = false): int
    {
        $span = match ($this) {
            self::Portrait => 4,
            self::Square => 5,
            self::Landscape => 6,
            self::Panorama => 8,
        };

        return min(Archive::WALL_COLUMNS, $featured ? $span + 2 : $span);
    }
}
