<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The branded error pages (resources/views/errors): one short line in the
 * tagline voice per status code and the label of the pill button home.
 */
final class ErrorPages
{
    public const int FORBIDDEN = 403;

    public const int NOT_FOUND = 404;

    public const int PAGE_EXPIRED = 419;

    public const int SERVER_ERROR = 500;

    public const int UNAVAILABLE = 503;

    public const string LABEL_PREFIX = 'Error';

    public const string HOME_LABEL = 'Return home';

    public const string SEARCH_LABEL = 'Search the archive';

    public const string CHAPTERS_LABEL = 'Or turn to a chapter';

    /**
     * Status code => heading and the Cormorant line beneath it.
     *
     * @return array<int, array{title: string, line: string}>
     */
    public static function all(): array
    {
        return [
            self::FORBIDDEN => [
                'title' => 'Not for these eyes',
                'line' => 'This page is kept for those with permission to see it.',
            ],
            self::NOT_FOUND => [
                'title' => 'Page not found',
                'line' => 'This page has been misplaced in the archive.',
            ],
            self::PAGE_EXPIRED => [
                'title' => 'The page has expired',
                'line' => 'Too much time passed in quiet. Please go back and try once more.',
            ],
            self::SERVER_ERROR => [
                'title' => 'Something went wrong',
                'line' => 'The fault is ours, not yours. Please return in a little while.',
            ],
            self::UNAVAILABLE => [
                'title' => 'Back shortly',
                'line' => 'The site is resting for a moment of care. Please return soon.',
            ],
        ];
    }

    /**
     * What a status code means, in plain words (the admin names each error page by it).
     */
    public static function describe(int $code): string
    {
        return match ($code) {
            self::FORBIDDEN => 'no permission',
            self::NOT_FOUND => 'page not found',
            self::PAGE_EXPIRED => 'form expired',
            self::UNAVAILABLE => 'site down for maintenance',
            default => 'something broke',
        };
    }

    /**
     * @return list<int>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function isValid(int $code): bool
    {
        return array_key_exists($code, self::all());
    }

    /**
     * The copy for a status code; unknown codes read as a server error.
     *
     * @return array{title: string, line: string}
     */
    public static function get(int $code): array
    {
        return self::all()[$code] ?? self::all()[self::SERVER_ERROR];
    }
}
