<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Geometry of the "MK" mark. The letters are the Cormorant Garamond Light
 * outlines as a single SVG path, so the mark never depends on the web font
 * having loaded and renders identically in the page, the favicon and PNG icons.
 */
final class BrandMark
{
    public const string TITLE = 'Professor Mohamed Osman Khidir';

    /** The SVG coordinate space is VIEWBOX x VIEWBOX; the ring fills it. */
    public const int VIEWBOX = 96;

    /** Hairline ring: 1px at every size (vector-effect: non-scaling-stroke). */
    public const int RING_STROKE_PX = 1;

    /** Below this rendered size the ring is replaced by a solid evergreen disc with ivory letters. */
    public const int DISC_BELOW_PX = 32;

    /** Clear space on every side, as a fraction of the ring diameter. */
    public const float CLEAR_SPACE_RATIO = 1 / 6;

    /** Default rendered size (ring diameter) in CSS pixels. */
    public const int DEFAULT_SIZE_PX = 48;

    /** Letters on a dark surface. */
    public const string TONE_IVORY = 'ivory';

    /** Letters on a light surface. */
    public const string TONE_EVERGREEN = 'evergreen';

    public const string DEFAULT_TONE = self::TONE_IVORY;

    /**
     * "MK" in Cormorant Garamond Light at 44 units, optically centred in the viewbox.
     * Generated from the font outlines; do not edit by hand.
     */
    public const string LETTERS_PATH = 'M20.1 57.17 20.54 35.26 21.51 35.35 21.12 57.17Q21.07 59.42 21.84 60.32Q22.61 61.22 24.81 61.22Q24.9 61.22 24.9 61.49Q24.9 61.75 24.81 61.75Q23.84 61.75 22.81 61.71Q21.78 61.66 20.54 61.66Q19.49 61.66 18.52 61.71Q17.55 61.75 16.67 61.75Q16.58 61.75 16.58 61.49Q16.58 61.22 16.67 61.22Q18.52 61.22 19.29 60.32Q20.06 59.42 20.1 57.17ZM50.33 61.22Q50.46 61.22 50.46 61.49Q50.46 61.75 50.33 61.75Q49.28 61.75 47.98 61.71Q46.68 61.66 45.23 61.66Q43.86 61.66 42.72 61.71Q41.58 61.75 40.65 61.75Q40.56 61.75 40.56 61.49Q40.56 61.22 40.65 61.22Q42.85 61.22 43.49 60.65Q44.13 60.08 44.04 58.19L43.25 36.49L44.52 34.87L33 61.44Q32.95 61.62 32.64 61.62Q32.34 61.62 32.25 61.44L21.51 37.9Q20.1 34.78 16.98 34.78Q16.89 34.78 16.89 34.51Q16.89 34.25 16.98 34.25Q17.64 34.25 18.39 34.29Q19.14 34.34 19.62 34.34Q20.24 34.34 20.76 34.29Q21.29 34.25 21.56 34.25Q22.17 34.25 22.52 34.65Q22.88 35.04 23.58 36.54L33.66 58.32L32.51 60.17L43.34 35.35Q43.56 34.87 43.93 34.56Q44.3 34.25 44.66 34.25Q44.88 34.25 45.32 34.29Q45.76 34.34 46.33 34.34Q47.47 34.34 48.18 34.29Q48.88 34.25 49.67 34.25Q49.76 34.25 49.76 34.51Q49.76 34.78 49.67 34.78Q47.52 34.78 46.5 35.33Q45.49 35.88 45.54 37.73L46.37 58.19Q46.42 59.46 46.7 60.12Q46.99 60.78 47.82 61Q48.66 61.22 50.33 61.22ZM56.89 58.19V37.81Q56.89 36.54 56.6 35.9Q56.32 35.26 55.48 35.02Q54.64 34.78 52.97 34.78Q52.88 34.78 52.88 34.51Q52.88 34.25 52.97 34.25Q54.03 34.25 55.33 34.32Q56.62 34.38 58.03 34.38Q59.57 34.38 60.85 34.32Q62.12 34.25 63.14 34.25Q63.22 34.25 63.22 34.51Q63.22 34.78 63.14 34.78Q61.46 34.78 60.63 35.04Q59.79 35.31 59.51 35.97Q59.22 36.63 59.22 37.9V58.19Q59.22 59.46 59.48 60.12Q59.75 60.78 60.61 61Q61.46 61.22 63.14 61.22Q63.22 61.22 63.22 61.49Q63.22 61.75 63.14 61.75Q62.08 61.75 60.83 61.71Q59.57 61.66 58.03 61.66Q56.62 61.66 55.3 61.71Q53.98 61.75 52.93 61.75Q52.84 61.75 52.84 61.49Q52.84 61.22 52.93 61.22Q54.6 61.22 55.46 61Q56.32 60.78 56.6 60.12Q56.89 59.46 56.89 58.19ZM67.76 55.37 60.54 46.39 62.39 45.07 68.72 53.04Q70.79 55.63 72.22 57.24Q73.65 58.85 74.62 59.68Q75.59 60.52 76.34 60.85Q77.08 61.18 77.77 61.2Q78.45 61.22 79.28 61.22Q79.42 61.22 79.42 61.49Q79.42 61.75 79.28 61.75Q77.3 61.75 76.12 61.75Q74.93 61.75 74.36 61.75Q74.05 61.75 73.85 61.77Q73.65 61.79 73.43 61.79Q72.82 61.79 72.46 61.57Q72.11 61.35 71.65 60.69Q71.19 60.03 70.31 58.76Q69.43 57.48 67.76 55.37ZM57.59 48.81 68.9 37.55Q70.57 35.88 70.37 35.33Q70.18 34.78 67.84 34.78Q67.76 34.78 67.76 34.51Q67.76 34.25 67.84 34.25Q68.9 34.25 69.98 34.32Q71.06 34.38 72.82 34.38Q74.44 34.38 75.39 34.32Q76.34 34.25 77.22 34.25Q77.35 34.25 77.35 34.51Q77.35 34.78 77.22 34.78Q75.72 34.78 73.74 35.39Q71.76 36.01 69.91 37.81L58.65 48.77Z';

    /**
     * @return list<string>
     */
    public static function tones(): array
    {
        return [self::TONE_IVORY, self::TONE_EVERGREEN];
    }

    public static function isDisc(int $size): bool
    {
        return $size < self::DISC_BELOW_PX;
    }

    /** Clear space in CSS pixels for a given ring diameter. */
    public static function clearSpace(int $size): float
    {
        return round($size * self::CLEAR_SPACE_RATIO, 2);
    }

    /** Hex colour of the letters for a tone (the disc variant is always ivory on evergreen). */
    public static function inkFor(string $tone, int $size): string
    {
        if (self::isDisc($size)) {
            return Brand::IVORY;
        }

        return $tone === self::TONE_EVERGREEN ? Brand::EVERGREEN : Brand::IVORY;
    }
}
