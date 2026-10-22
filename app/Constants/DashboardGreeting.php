<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The greeting at the top of the admin dashboard: a salutation for the hour of
 * the day and the date beneath it in small caps (DateFormats::GREETING).
 */
final class DashboardGreeting
{
    public const string MORNING = 'Good morning';

    public const string AFTERNOON = 'Good afternoon';

    public const string EVENING = 'Good evening';

    /** First hour (24h) of the afternoon and of the evening. */
    public const int AFTERNOON_FROM = 12;

    public const int EVENING_FROM = 18;

    /**
     * The salutation for an hour of the day (0–23).
     */
    public static function salutation(int $hour): string
    {
        return match (true) {
            $hour < 0 || $hour > 23 => throw new \InvalidArgumentException(sprintf('"%d" is not an hour of the day.', $hour)),
            $hour < self::AFTERNOON_FROM => self::MORNING,
            $hour < self::EVENING_FROM => self::AFTERNOON,
            default => self::EVENING,
        };
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::MORNING, self::AFTERNOON, self::EVENING];
    }
}
