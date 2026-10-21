<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Custom property keys stored on every media item (Media::custom_properties).
 */
final class MediaProperties
{
    public const string ALT_TEXT = 'alt_text';

    public const string CAPTION = 'caption';

    public const string PLACE = 'place';

    public const string YEAR = 'year';

    public const string CREDIT = 'credit';

    public const string PERMISSIONS_NOTE = 'permissions_note';

    /** Optional one-line note on the museum label below the caption. */
    public const string NOTE = 'note';

    /** One of MediaTreatment. */
    public const string TREATMENT = 'treatment';

    /** Album photos: true marks the cover. */
    public const string IS_COVER = 'is_cover';

    /** Pixel size of the original image, recorded at upload so <img> can reserve its space. */
    public const string WIDTH = 'width';

    public const string HEIGHT = 'height';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ALT_TEXT, self::CAPTION, self::PLACE, self::YEAR, self::NOTE,
            self::CREDIT, self::PERMISSIONS_NOTE, self::TREATMENT, self::IS_COVER,
            self::WIDTH, self::HEIGHT,
        ];
    }

    /**
     * Properties an image needs before it may appear on the public site.
     *
     * @return list<string>
     */
    public static function requiredForPublishing(): array
    {
        return [self::ALT_TEXT, self::CAPTION, self::PLACE, self::YEAR];
    }
}
