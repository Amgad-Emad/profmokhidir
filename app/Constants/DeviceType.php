<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * Device type of an analytics page view, told from the user agent.
 */
enum DeviceType: string
{
    use HasValues;

    case Mobile = 'mobile';
    case Tablet = 'tablet';
    case Desktop = 'desktop';

    public function label(): string
    {
        return match ($this) {
            self::Mobile => __('Mobile'),
            self::Tablet => __('Tablet'),
            self::Desktop => __('Desktop'),
        };
    }
}
