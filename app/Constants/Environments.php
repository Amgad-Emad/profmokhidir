<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Application environment names, so they are never typed as loose strings.
 */
final class Environments
{
    public const string LOCAL = 'local';

    public const string TESTING = 'testing';

    public const string PRODUCTION = 'production';

    /**
     * Environments in which developer-only pages (such as /styleguide) may be served.
     *
     * @return list<string>
     */
    public static function developer(): array
    {
        return [self::LOCAL];
    }
}
