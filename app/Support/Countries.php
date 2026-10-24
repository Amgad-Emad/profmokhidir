<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\Analytics;
use Locale;

/**
 * Country names and flag emoji for ISO 3166 alpha-2 codes (the analytics
 * country lists). "ZZ" is the unknown country: no flag, named "Unknown".
 */
final class Countries
{
    public static function normalise(?string $code): string
    {
        $code = mb_strtoupper(trim((string) $code));

        // Cloudflare sends XX (no data) and T1 (Tor); neither is a country.
        if (preg_match('/^[A-Z]{2}$/', $code) !== 1 || in_array($code, ['XX', 'T1', Analytics::UNKNOWN_COUNTRY], true)) {
            return Analytics::UNKNOWN_COUNTRY;
        }

        return $code;
    }

    public static function isKnown(string $code): bool
    {
        return self::normalise($code) !== Analytics::UNKNOWN_COUNTRY;
    }

    public static function name(string $code): string
    {
        $code = self::normalise($code);

        if ($code === Analytics::UNKNOWN_COUNTRY) {
            return Analytics::UNKNOWN_LABEL;
        }

        $name = class_exists(Locale::class) ? Locale::getDisplayRegion('-'.$code, 'en') : '';

        return $name !== '' && $name !== $code ? $name : $code;
    }

    /**
     * The flag emoji (two regional indicator symbols), or an empty string for the unknown country.
     */
    public static function flag(string $code): string
    {
        $code = self::normalise($code);

        if ($code === Analytics::UNKNOWN_COUNTRY) {
            return '';
        }

        return mb_chr(0x1F1E6 + ord($code[0]) - ord('A')).mb_chr(0x1F1E6 + ord($code[1]) - ord('A'));
    }
}
