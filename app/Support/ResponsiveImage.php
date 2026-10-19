<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\MediaConversions;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Reads what the public site needs from a stored image: the generated WebP
 * conversions as src/srcset, the treatment class and the recorded pixel size.
 */
final class ResponsiveImage
{
    public const string CLASS_PORTRAIT = 'photo-portrait';

    public const string CLASS_ARCHIVE = 'photo-archive';

    /**
     * Largest generated conversion, falling back to the original upload.
     */
    public static function largestUrl(Media $media): string
    {
        foreach (array_reverse(MediaConversions::names()) as $name) {
            if ($media->hasGeneratedConversion($name)) {
                return $media->getUrl($name);
            }
        }

        return $media->getUrl();
    }

    public static function srcset(Media $media): ?string
    {
        $sources = [];

        foreach (MediaConversions::widths() as $name => $width) {
            if ($media->hasGeneratedConversion($name)) {
                $sources[] = $media->getUrl($name).' '.$width.'w';
            }
        }

        return $sources === [] ? null : implode(', ', $sources);
    }

    /**
     * CSS class for the brand treatment chosen for this image (portrait unless marked archive).
     */
    public static function treatmentClass(Media $media): string
    {
        $treatment = MediaTreatment::tryFrom((string) $media->getCustomProperty(MediaProperties::TREATMENT));

        return $treatment === MediaTreatment::Archive ? self::CLASS_ARCHIVE : self::CLASS_PORTRAIT;
    }

    /**
     * Pixel size recorded at upload, or null when unknown.
     *
     * @return array{int, int}|null
     */
    public static function dimensions(Media $media): ?array
    {
        $width = (int) $media->getCustomProperty(MediaProperties::WIDTH, 0);
        $height = (int) $media->getCustomProperty(MediaProperties::HEIGHT, 0);

        return $width > 0 && $height > 0 ? [$width, $height] : null;
    }

    /**
     * Trimmed text property, or null when blank.
     */
    public static function text(Media $media, string $property): ?string
    {
        $value = $media->getCustomProperty($property);

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }

    /**
     * "Place, Year" in the label style, or null when neither is known.
     */
    public static function placeYear(Media $media): ?string
    {
        $parts = array_filter([self::text($media, MediaProperties::PLACE), self::text($media, MediaProperties::YEAR)]);

        return $parts === [] ? null : implode(', ', $parts);
    }
}
