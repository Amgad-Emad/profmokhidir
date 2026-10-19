<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Responsive WebP conversions registered on every image collection.
 */
final class MediaConversions
{
    public const string SMALL = 'w480';

    public const string MEDIUM = 'w960';

    public const string LARGE = 'w1600';

    public const string FORMAT = 'webp';

    /**
     * @return array<string, int> conversion name => width in px
     */
    public static function widths(): array
    {
        return [
            self::SMALL => 480,
            self::MEDIUM => 960,
            self::LARGE => 1600,
        ];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::widths());
    }
}
