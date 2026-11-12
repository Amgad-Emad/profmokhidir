<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The suggested themes (Admin › Appearance): read-only templates that can be
 * applied or duplicated. "Evergreen & Ivory" is the brand (every token at its
 * brand value, ThemeTokens::defaults) and the recommended default; the others
 * are alternative palettes that move away from the approved brand guidelines.
 * Each alternative names its Basic colours and the few tokens it wants
 * different from what ThemePaletteService derives; everything else is
 * derived. tests/Unit/Services/ThemePaletteServiceTest checks every preset
 * in both modes against WCAG 2.2 AA.
 */
final class ThemePresets
{
    public const string BRAND = 'evergreen-ivory';

    public const string OXFORD = 'oxford-navy-parchment';

    public const string BURGUNDY = 'burgundy-library';

    public const string KHARTOUM = 'khartoum-sand-nile';

    public const string GRAPHITE = 'graphite-paper';

    public const string OLIVE = 'olive-manuscript';

    /**
     * Slug => definition, in display order.
     *
     * @return array<string, array{name: string, description: string, light: array<string, string>, dark: array<string, string>}>
     */
    public static function all(): array
    {
        return [
            self::BRAND => [
                'name' => 'Evergreen & Ivory',
                'description' => 'The approved brand palette: evergreen, ivory and brass.',
                'light' => ThemeTokens::defaults(Theme::Light),
                'dark' => ThemeTokens::defaults(Theme::Dark),
            ],
            self::OXFORD => [
                'name' => 'Oxford Navy & Parchment',
                'description' => 'Deep navy, parchment and antique gold.',
                'light' => ['surface' => '#f4efe3', 'surface-raised' => '#e8dfcb', 'primary' => '#14213d', 'accent-line' => '#a5813f', 'ink' => '#14213d', 'danger' => '#9b2c2c', 'mark-ring' => '#a5813f'],
                'dark' => ['surface' => '#0f1a30', 'surface-raised' => '#1f2e4d', 'primary' => '#0f1a30', 'accent-line' => '#c5a35e', 'ink' => '#e6dcc3', 'danger' => '#efb0a5', 'ink-strong' => '#f4efe3', 'mark-ring' => '#c5a35e'],
            ],
            self::BURGUNDY => [
                'name' => 'Burgundy Library',
                'description' => 'Deep burgundy, cream and aged brass.',
                'light' => ['surface' => '#f6f0e4', 'surface-raised' => '#ebe0cc', 'primary' => '#4a1621', 'accent-line' => '#a07a3c', 'ink' => '#3a1219', 'danger' => '#9b2c2c', 'mark-ring' => '#a07a3c'],
                'dark' => ['surface' => '#260c12', 'surface-raised' => '#43212a', 'primary' => '#260c12', 'accent-line' => '#c29a5b', 'ink' => '#ead9c0', 'danger' => '#efb0a5', 'ink-strong' => '#f6f0e4', 'mark-ring' => '#c29a5b'],
            ],
            self::KHARTOUM => [
                'name' => 'Khartoum Sand & Nile',
                'description' => 'Warm sand, deep Nile blue-green and copper.',
                'light' => ['surface' => '#f3ebdd', 'surface-raised' => '#e6d8c1', 'primary' => '#0a3634', 'accent-line' => '#b8733f', 'ink' => '#123332', 'danger' => '#9b2c2c', 'mark-ring' => '#b8733f'],
                'dark' => ['surface' => '#061c1b', 'surface-raised' => '#0e2a29', 'primary' => '#061c1b', 'accent-line' => '#d08a5a', 'ink' => '#eadfcb', 'danger' => '#efb0a5', 'ink-strong' => '#f3ebdd', 'mark-ring' => '#d08a5a'],
            ],
            self::GRAPHITE => [
                'name' => 'Graphite & Paper',
                'description' => 'Near-black graphite, warm white and muted gold.',
                'light' => ['surface' => '#f7f5f0', 'surface-raised' => '#ebe8e1', 'primary' => '#1f2023', 'accent-line' => '#a38b5a', 'ink' => '#1f2023', 'danger' => '#9b2c2c', 'mark-ring' => '#a38b5a'],
                'dark' => ['surface' => '#17181a', 'surface-raised' => '#2a2c30', 'primary' => '#17181a', 'accent-line' => '#b9a06e', 'ink' => '#e4e0d6', 'danger' => '#efb0a5', 'ink-strong' => '#f7f5f0', 'mark-ring' => '#b9a06e'],
            ],
            self::OLIVE => [
                'name' => 'Olive Manuscript',
                'description' => 'Olive green, vellum and bronze.',
                'light' => ['surface' => '#f2eedf', 'surface-raised' => '#e4ddc7', 'primary' => '#2c331b', 'accent-line' => '#a17c40', 'ink' => '#2e3420', 'danger' => '#9b2c2c', 'mark-ring' => '#a17c40'],
                'dark' => ['surface' => '#1d2214', 'surface-raised' => '#343c24', 'primary' => '#1d2214', 'accent-line' => '#b48c55', 'ink' => '#e3dcc2', 'danger' => '#efb0a5', 'ink-strong' => '#f2eedf', 'mark-ring' => '#b48c55'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }

    public static function isPreset(?string $slug): bool
    {
        return $slug !== null && array_key_exists($slug, self::all());
    }

    /**
     * @return array{name: string, description: string, light: array<string, string>, dark: array<string, string>}
     */
    public static function get(string $slug): array
    {
        return self::all()[$slug] ?? throw new \InvalidArgumentException(sprintf('Unknown theme preset "%s".', $slug));
    }
}
