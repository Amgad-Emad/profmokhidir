<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The interaction states every ButtonVariant defines colours for. `selector()` is
 * what resources/css/buttons.css matches for the state; the Playwright check
 * (tests/qa/buttons.mjs) drives the same states in the browser.
 */
enum ButtonState: string
{
    case Default = 'default';
    case Hover = 'hover';
    case Active = 'active';
    case FocusVisible = 'focus';
    case Disabled = 'disabled';
    case Loading = 'loading';

    /** The suffix on the variant's token names ("" for the default state). */
    public function tokenSuffix(): string
    {
        return $this === self::Default ? '' : '-'.$this->value;
    }

    /** The CSS condition for the state on a `.btn`. */
    public function selector(): string
    {
        return match ($this) {
            self::Default => '',
            self::Hover => ':hover',
            self::Active => ':active',
            self::FocusVisible => ':focus-visible',
            self::Disabled => ':disabled, [aria-disabled="true"]',
            self::Loading => '[aria-busy="true"]',
        };
    }
}
