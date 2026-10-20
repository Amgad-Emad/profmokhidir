<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * What the brand audit (tests/Unit/Design/BrandAuditTest) scans and what it
 * accepts. Anything outside these lists is reported as off-brand.
 */
final class BrandAudit
{
    /**
     * Globs of source files to scan, relative to the project root.
     *
     * @return list<string>
     */
    public static function sourceGlobs(): array
    {
        return [
            'resources/css/*.css',
            'resources/js/*.js',
            'resources/views/**/*.blade.php',
            'resources/views/*.blade.php',
        ];
    }

    /**
     * Globs of built assets to scan when a build exists.
     *
     * @return list<string>
     */
    public static function buildGlobs(): array
    {
        return ['public/build/assets/*.css'];
    }

    /**
     * Hex colours allowed besides the nine brand tokens: pure white and black
     * (Brand::permittedExtras) and fully transparent values.
     *
     * @return list<string>
     */
    public static function permittedHex(): array
    {
        return array_map('strtoupper', [
            ...Brand::colours(),
            ...Brand::permittedExtras(),
            '#FFF', '#000', '#0000', '#00000000', '#FFF0', '#FFFFFF00',
        ]);
    }

    /**
     * Font families the stylesheet may name directly (the three brand fonts
     * and the generic fallbacks that follow them in the theme tokens).
     *
     * @return list<string>
     */
    public static function permittedFontFamilies(): array
    {
        return [
            Typography::DISPLAY_FAMILY,
            Typography::SANS_FAMILY,
            Typography::MONO_FAMILY,
            'Cormorant', 'Georgia', 'Times New Roman', 'serif',
            'ui-sans-serif', 'system-ui', 'sans-serif',
            'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace',
        ];
    }

    /**
     * Named keyframe animations that may exist (each unhurried, nothing bounces).
     *
     * @return list<string>
     */
    public static function permittedKeyframes(): array
    {
        return ['lightbox-fade'];
    }

    /**
     * Words that identify a forbidden animation wherever they appear in a
     * keyframe name, an animation declaration or a utility class.
     *
     * @return list<string>
     */
    public static function forbiddenMotionWords(): array
    {
        return ['bounce', 'spin', 'ping', 'pulse', 'wobble', 'shake', 'flash', 'blink', 'jello', 'rubber', 'swing', 'tada'];
    }

    /**
     * The only permitted gradient: a soft radial brass glow on evergreen.
     */
    public const string GRADIENT_FUNCTION = 'radial-gradient';

    /**
     * File => reason, for files the audit skips entirely.
     *
     * @return array<string, string>
     */
    public static function exemptFiles(): array
    {
        return [
            'resources/views/styleguide/index.blade.php' => 'Local-only design review page (404 outside local). Its inline SVG sample scene is deliberately colourful so the photo treatments visibly change it.',
        ];
    }
}
