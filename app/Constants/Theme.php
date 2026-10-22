<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The colour theme a visitor has chosen. System follows prefers-color-scheme;
 * Light and Dark are forced through data-theme on <html>. Stored on the user
 * (users.theme), in a cookie for guests and mirrored in localStorage
 * (App\Constants\ThemeSettings).
 */
enum Theme: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';

    public const self DEFAULT = self::System;

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

    /** Light or Dark: the page is forced to one scheme regardless of the system. */
    public function isExplicit(): bool
    {
        return $this !== self::System;
    }

    public function label(): string
    {
        return match ($this) {
            self::System => 'System',
            self::Light => 'Light',
            self::Dark => 'Dark',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::System => 'Follow the device setting',
            self::Light => 'Ivory page, evergreen ink',
            self::Dark => 'Evergreen page, champagne ink',
        };
    }

    /** Name of the inline icon in the theme control (see the x-icon component). */
    public function icon(): string
    {
        return match ($this) {
            self::System => 'theme-system',
            self::Light => 'theme-light',
            self::Dark => 'theme-dark',
        };
    }
}
