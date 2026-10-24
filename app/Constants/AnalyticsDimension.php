<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * The small lists of the analytics report kept in analytics_daily_technologies.
 */
enum AnalyticsDimension: string
{
    use HasValues;

    case Device = 'device';
    case Browser = 'browser';
    case UtmSource = 'utm_source';

    public function label(): string
    {
        return match ($this) {
            self::Device => 'Devices',
            self::Browser => 'Browsers',
            self::UtmSource => 'Campaign sources',
        };
    }

    /**
     * The analytics_events column the dimension is read from.
     */
    public function column(): string
    {
        return $this->value;
    }
}
