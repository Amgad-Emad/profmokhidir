<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Cuts a referrer URL down to its domain: lower-case, without "www." and
 * without path, query or credentials. Referrers from the site itself, and
 * anything that is not an http(s) URL, count as no referrer.
 */
final class ReferrerDomain
{
    public const int MAX_LENGTH = 120;

    public static function from(?string $referrer, ?string $ownHost = null): ?string
    {
        $referrer = trim((string) $referrer);

        if ($referrer === '') {
            return null;
        }

        $parts = parse_url($referrer);

        if (! is_array($parts) || ! in_array(mb_strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || ! isset($parts['host'])) {
            return null;
        }

        $host = self::bare($parts['host']);

        if ($host === '' || preg_match('/^[a-z0-9.-]+$/', $host) !== 1 || mb_strlen($host) > self::MAX_LENGTH) {
            return null;
        }

        if ($ownHost !== null && $host === self::bare($ownHost)) {
            return null;
        }

        return $host;
    }

    private static function bare(string $host): string
    {
        $host = rtrim(mb_strtolower(trim($host)), '.');

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
