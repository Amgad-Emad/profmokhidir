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
     * The one file that may name a brand colour: the raw palette (--brand-*)
     * and the semantic tokens built from it. Everywhere else uses the tokens.
     */
    public const string TOKEN_FILE = 'resources/css/tokens.css';

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
     * Hex colours allowed besides the nine brand tokens: the functional danger
     * red (Brand::functionalColours, exactly these two values), pure white and
     * black (Brand::permittedExtras) and fully transparent values.
     *
     * @return list<string>
     */
    public static function permittedHex(): array
    {
        return array_map('strtoupper', [
            ...Brand::colours(),
            ...Brand::functionalColours(),
            ...Brand::permittedExtras(),
            '#FFF', '#000', '#0000', '#00000000', '#FFF0', '#FFFFFF00',
        ]);
    }

    /**
     * Utility prefixes that take a colour (Tailwind v4). A brand colour name
     * after one of these is a raw colour class and must be a semantic token instead.
     *
     * @return list<string>
     */
    public static function colourUtilityPrefixes(): array
    {
        return [
            'text', 'bg', 'border', 'border-x', 'border-y', 'border-s', 'border-e', 'border-t', 'border-r', 'border-b', 'border-l',
            'ring', 'ring-offset', 'inset-ring', 'fill', 'stroke', 'outline', 'decoration', 'divide', 'divide-x', 'divide-y',
            'placeholder', 'caret', 'accent', 'shadow', 'inset-shadow', 'from', 'via', 'to',
        ];
    }

    /**
     * Matches a raw brand colour class (bg-evergreen, text-brass/80, hover:text-ivory ...).
     */
    public static function rawColourClassPattern(): string
    {
        $prefixes = implode('|', array_map('preg_quote', self::colourUtilityPrefixes()));
        $names = implode('|', array_keys(Brand::colours()));

        return '/(?<![\w-])(?:'.$prefixes.')-(?:'.$names.')(?:\/\d{1,3})?(?![\w-])/';
    }

    /**
     * Matches a raw brand colour variable (--brand-brass, --color-evergreen) or a raw
     * functional colour (--functional-danger-light) wherever it is read.
     */
    public static function rawColourVariablePattern(): string
    {
        $names = implode('|', array_keys(Brand::colours()));
        $functional = implode('|', array_keys(Brand::functionalColours()));

        return '/--(?:(?:brand|color)-(?:'.$names.')|functional-(?:'.$functional.'))(?![\w-])/';
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
     * Named keyframe animations that may exist (each unhurried, nothing bounces):
     * the lightbox fade, the skeleton's slow breath, and the hairline that draws
     * along the foot of a form control while it loads (1600ms, the rule timing),
     * in the dashboard the drawer that slides in from the edge and the toast
     * that fades in (600ms on the brand easing, off for reduced motion), and in
     * the community chat a new message rising 8px into place (300ms, off for
     * reduced motion).
     *
     * @return list<string>
     */
    public static function permittedKeyframes(): array
    {
        return ['lightbox-fade', 'skeleton-breathe', 'form-draw', 'admin-drawer-in', 'admin-toast-in', 'chat-arrive'];
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
     * The semantic token that carries the glow colour (brass at 22%).
     */
    public const string GLOW_TOKEN = '--accent-glow';

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
