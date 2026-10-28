<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * Row spacing of an admin list, chosen with the toolbar's density toggle and
 * remembered per screen for each user.
 */
enum ListDensity: string
{
    use HasValues;

    case Comfortable = 'comfortable';
    case Compact = 'compact';

    public const self DEFAULT = self::Comfortable;

    public function label(): string
    {
        return match ($this) {
            self::Comfortable => __('Comfortable rows'),
            self::Compact => __('Compact rows'),
        };
    }

    public function other(): self
    {
        return $this === self::Comfortable ? self::Compact : self::Comfortable;
    }
}
