<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The museum archive of Pictures and Videos: the decade and featured columns on
 * `media` (album photos) and `videos`, the decades editors may choose, the
 * exhibition wall's grid and the query string that remembers the Pictures view.
 */
final class Archive
{
    /** Column holding an editor's decade override (e.g. 1970). */
    public const string DECADE = 'decade';

    /** Column flagging a featured photo or video. */
    public const string FEATURED = 'is_featured';

    /** Earliest decade editors may choose. */
    public const int FIRST_DECADE = 1930;

    /** Query string key of the Pictures view (PictureView). */
    public const string QUERY_VIEW = 'view';

    /** Columns of the exhibition wall at tablet width and above. */
    public const int WALL_COLUMNS = 12;

    /** Widest the wall gets (the page container, max-w-7xl less its padding), for `sizes`. */
    public const int WALL_MAX_WIDTH = 1232;

    /** Heading of the group for photos and videos without a year or date. */
    public const string UNDATED = 'Undated';

    /** How many other recordings "More from this event" lists. */
    public const int RELATED_LIMIT = 3;

    /**
     * Decades editors may choose, newest last.
     *
     * @return list<int>
     */
    public static function decades(): array
    {
        return range(self::FIRST_DECADE, self::decadeOf((int) date('Y')), 10);
    }

    /**
     * decade => label, for the admin select.
     *
     * @return array<int, string>
     */
    public static function decadeOptions(): array
    {
        $options = [];

        foreach (self::decades() as $decade) {
            $options[$decade] = self::label($decade);
        }

        return $options;
    }

    public static function decadeOf(int $year): int
    {
        return intdiv($year, 10) * 10;
    }

    /**
     * "1970s", or "Undated" for null.
     */
    public static function label(?int $decade): string
    {
        if ($decade === null) {
            return __(self::UNDATED);
        }

        // English writes "1970s"; other languages name the decade in a sentence of their own.
        return app()->getLocale() === Locales::DEFAULT ? $decade.'s' : __('Decade of :decade', ['decade' => $decade]);
    }
}
