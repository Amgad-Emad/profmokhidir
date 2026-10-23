<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The colour theme: Light or Dark, nothing else. Before a visitor chooses,
 * prefers-color-scheme decides (no value is stored); once they click the
 * toggle, the choice is kept on the account (users.theme), in a cookie and in
 * localStorage (App\Constants\ThemeSettings) and forced through data-theme.
 */
enum Theme: string
{
    case Light = 'light';
    case Dark = 'dark';

    /**
     * Read only by the 2026_10_22 migration that added users.theme (migrations that
     * have run are never edited). Since 2026_10_23 the column is NULL until the
     * person chooses, so nothing else should use this.
     */
    public const self DEFAULT = self::Light;

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $theme): string => $theme->value, self::cases());
    }

    public static function tryFromValue(mixed $value): ?self
    {
        return is_string($value) ? self::tryFrom($value) : null;
    }

    /** The theme one click of the toggle switches to. */
    public function opposite(): self
    {
        return $this === self::Dark ? self::Light : self::Dark;
    }

    /** The toggle's accessible name while this theme is showing ("Switch to dark theme"). */
    public function switchLabel(): string
    {
        return sprintf(ThemeSettings::SWITCH_LABEL, strtolower($this->opposite()->label()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Light => 'Light',
            self::Dark => 'Dark',
        };
    }

    /**
     * The icon the toggle shows while this theme is showing: the sun in the dark theme
     * (click for daylight), the moon in the light theme (see the x-icon component).
     */
    public function icon(): string
    {
        return match ($this) {
            self::Light => 'theme-dark',
            self::Dark => 'theme-light',
        };
    }
}
