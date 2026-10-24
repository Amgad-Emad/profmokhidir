<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\Analytics;
use App\Constants\DeviceType;

/**
 * Reads what analytics needs from a user-agent string, in memory only: is it
 * a bot, which device type, which browser family. The string itself is never
 * stored.
 */
final class UserAgent
{
    public const string OTHER_BROWSER = 'Other';

    public static function isBot(?string $userAgent): bool
    {
        $userAgent = mb_strtolower(trim((string) $userAgent));

        if ($userAgent === '' || ! str_contains($userAgent, 'mozilla/')) {
            return true;
        }

        foreach (Analytics::botPatterns() as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public static function device(?string $userAgent): DeviceType
    {
        $userAgent = (string) $userAgent;

        return match (true) {
            preg_match('/iPad|Tablet|PlayBook|Silk|Kindle|Nexus (7|9|10)|SM-T\d|Android(?!.*Mobile)/i', $userAgent) === 1 => DeviceType::Tablet,
            preg_match('/Mobi|iPhone|iPod|Windows Phone|Android.*Mobile|BlackBerry|Opera Mini/i', $userAgent) === 1 => DeviceType::Mobile,
            default => DeviceType::Desktop,
        };
    }

    /**
     * The browser family (Edge, Opera, Samsung Internet, Firefox, Chrome, Safari or Other).
     */
    public static function browser(?string $userAgent): string
    {
        $userAgent = (string) $userAgent;

        return match (true) {
            preg_match('/Edg(e|A|iOS)?\//', $userAgent) === 1 => 'Edge',
            preg_match('/OPR\/|Opera/', $userAgent) === 1 => 'Opera',
            str_contains($userAgent, 'SamsungBrowser/') => 'Samsung Internet',
            preg_match('/Firefox\/|FxiOS\//', $userAgent) === 1 => 'Firefox',
            preg_match('/Chrome\/|CriOS\/|Chromium\//', $userAgent) === 1 => 'Chrome',
            str_contains($userAgent, 'Safari/') && str_contains($userAgent, 'Version/') => 'Safari',
            default => self::OTHER_BROWSER,
        };
    }
}
