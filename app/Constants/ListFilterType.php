<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * How a filter of an admin list toolbar is chosen: one option (radio), any
 * number of options (checkboxes, kept comma-joined in the address), or a single
 * on/off condition (one checkbox, such as "Needs Arabic translation").
 */
enum ListFilterType: string
{
    use HasValues;

    case Single = 'single';
    case Multi = 'multi';
    case Flag = 'flag';

    public function label(): string
    {
        return match ($this) {
            self::Single => __('One option'),
            self::Multi => __('Any of several options'),
            self::Flag => __('On or off'),
        };
    }

    /**
     * The ARIA role of each option in the filter's popover.
     */
    public function optionRole(): string
    {
        return $this === self::Single ? 'menuitemradio' : 'menuitemcheckbox';
    }
}
