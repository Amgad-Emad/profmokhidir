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

    /** Album photos: true offers the photo to the home timeline ("A life in chapters") for its decade. */
    public const string ON_TIMELINE = 'on_timeline';

    /** Pixel size of the original image, recorded at upload so <img> can reserve its space. */
    public const string WIDTH = 'width';

    public const string HEIGHT = 'height';

    /** Page images: the crop focus point, as percentages from the left and the top (CSS object-position). */
    public const string FOCUS_X = 'focus_x';

    public const string FOCUS_Y = 'focus_y';

    /** Arabic (and any other non-default language) versions of the described properties: translations.ar.alt_text. */
    public const string TRANSLATIONS = 'translations';

    /**
     * Properties written in each language (the rest are the same in every language).
     *
     * @return list<string>
     */
    public static function translatable(): array
    {
        return [self::ALT_TEXT, self::CAPTION, self::PLACE, self::NOTE];
    }

    /**
     * Where a property's version in a language is kept ("translations.ar.alt_text").
     */
    public static function translationPath(string $locale, string $property): string
    {
        return self::TRANSLATIONS.'.'.$locale.'.'.$property;
    }

    /**
     * Whether a key is a translation path of a described property ("translations.ar.alt_text").
     */
    public static function isTranslationPath(string $key): bool
    {
        $parts = explode('.', $key);

        return count($parts) === 3 && $parts[0] === self::TRANSLATIONS && Locales::isValid($parts[1])
            && $parts[1] !== Locales::DEFAULT && in_array($parts[2], self::translatable(), true);
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ALT_TEXT, self::CAPTION, self::PLACE, self::YEAR, self::NOTE,
            self::CREDIT, self::PERMISSIONS_NOTE, self::TREATMENT, self::IS_COVER, self::ON_TIMELINE,
            self::WIDTH, self::HEIGHT, self::FOCUS_X, self::FOCUS_Y,
        ];
    }

    /**
     * The details that describe a photo. Optional; the media library flags
     * images missing any of them ("Needs details", the incomplete filter) as
     * a reminder, never a block.
     *
     * @return list<string>
     */
    public static function details(): array
    {
        return [self::ALT_TEXT, self::CAPTION, self::PLACE, self::YEAR];
    }

    /**
     * Properties an image needs before it may appear on the public site: none.
     * Alt text, caption, place and year are optional (owner's decision,
     * 23 November 2026); the public site renders only the ones filled in.
     *
     * @return list<string>
     */
    public static function requiredForPublishing(): array
    {
        return [];
    }
}
