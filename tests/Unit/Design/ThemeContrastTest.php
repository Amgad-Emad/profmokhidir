<?php

namespace Tests\Unit\Design;

use App\Constants\Brand;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * WCAG 2.2 AA contrast over the semantic tokens in resources/css/tokens.css,
 * computed from the file itself: every ink against every surface it can sit
 * on, in the light and the dark theme, on the page, inside an `.on-dark` band,
 * on the hero and on raised surfaces. Body text needs 4.5:1, focus rings and
 * strong rules 3:1. Translucent tokens are composited over their surface
 * first, as the browser does.
 */
class ThemeContrastTest extends TestCase
{
    private const float TEXT_MINIMUM = 4.5;

    private const float UI_MINIMUM = 3.0;

    /** Inks that carry text of any size. */
    private const array TEXT_INKS = ['ink', 'ink-strong', 'ink-muted', 'ink-quiet', 'accent-text', 'danger-tone', 'pending-tone', 'success-tone', 'neutral-tone'];

    /** Inks that sit inside a form field (its text, placeholder and hint). */
    private const array FIELD_INKS = ['ink', 'ink-strong', 'ink-muted', 'ink-quiet'];

    /** Tokens that mark a user-interface boundary (WCAG 1.4.11). */
    private const array UI_INKS = ['focus', 'rule-strong'];

    /** @var array<string, array<string, string>> selector => declarations */
    private array $blocks;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blocks = $this->parseBlocks(file_get_contents(resource_path('css/tokens.css')));
    }

    /**
     * Every context the tokens are used in: theme, region layers and whether the surface is raised.
     *
     * @return array<string, array{theme: string, layers: list<string>, raised: bool}>
     */
    public static function contexts(): array
    {
        $contexts = [];

        foreach (['light', 'dark'] as $theme) {
            foreach (['page' => [], 'band' => ['.on-dark'], 'hero' => ['.on-dark', '.glow-brass']] as $region => $layers) {
                foreach ([false, true] as $raised) {
                    $contexts["{$theme} {$region}".($raised ? ' raised' : '')] = ['theme' => $theme, 'layers' => $layers, 'raised' => $raised];
                }
            }
        }

        return $contexts;
    }

    #[Test]
    public function the_brand_palette_is_declared_once_and_matches_the_constants(): void
    {
        foreach (Brand::colours() as $name => $hex) {
            $this->assertSame(strtolower($hex), strtolower($this->blocks[':root']["--brand-{$name}"] ?? ''), "--brand-{$name}");
        }
    }

    #[Test]
    public function text_inks_reach_4_5_to_1_on_every_surface_in_every_context(): void
    {
        $failures = [];

        foreach (self::contexts() as $label => $context) {
            foreach ($this->surfacesFor($context) as $surfaceName => $surface) {
                foreach (str_contains($surfaceName, 'field') ? self::FIELD_INKS : self::TEXT_INKS as $ink) {
                    $ratio = $this->ratio($this->resolve($ink, $context), $surface);

                    if ($ratio < self::TEXT_MINIMUM) {
                        $failures[] = sprintf('%s: --%s on --%s is %.2f:1', $label, $ink, $surfaceName, $ratio);
                    }
                }
            }

            $pairs = [
                ['fill-ink', 'fill'],
                ['selection-ink', 'selection'],
                ['mark-disc-ink', 'mark-disc'],
            ];

            foreach ($pairs as [$ink, $surface]) {
                $ratio = $this->ratio($this->resolve($ink, $context), $this->resolve($surface, $context));

                if ($ratio < self::TEXT_MINIMUM) {
                    $failures[] = sprintf('%s: --%s on --%s is %.2f:1', $label, $ink, $surface, $ratio);
                }
            }
        }

        $this->assertSame([], $failures, "Text below 4.5:1:\n".implode("\n", $failures));
    }

    #[Test]
    public function focus_rings_and_strong_rules_reach_3_to_1_on_every_surface(): void
    {
        $failures = [];

        foreach (self::contexts() as $label => $context) {
            foreach ($this->surfacesFor($context) as $surfaceName => $surface) {
                foreach (self::UI_INKS as $ink) {
                    $ratio = $this->ratio($this->resolve($ink, $context), $surface);

                    if ($ratio < self::UI_MINIMUM) {
                        $failures[] = sprintf('%s: --%s on --%s is %.2f:1', $label, $ink, $surfaceName, $ratio);
                    }
                }
            }
        }

        $this->assertSame([], $failures, "UI contrast below 3:1:\n".implode("\n", $failures));
    }

    #[Test]
    public function the_band_inks_and_the_hero_ink_sit_on_evergreen_and_cypress(): void
    {
        foreach (['light', 'dark'] as $theme) {
            $context = ['theme' => $theme, 'layers' => [], 'raised' => false];
            $band = $this->resolve('surface-band', $context);

            foreach (['ink-on-band', 'ink-on-band-strong'] as $ink) {
                $this->assertGreaterThanOrEqual(self::TEXT_MINIMUM, $this->ratio($this->resolve($ink, $context), $band), "{$theme}: {$ink} on the band");
            }

            $this->assertGreaterThanOrEqual(self::TEXT_MINIMUM, $this->ratio($this->resolve('ink-on-hero', $context), $this->resolve('surface-hero', $context)), "{$theme}: ink on the hero");
        }
    }

    #[Test]
    public function the_brass_hairline_holds_3_to_1_on_the_page_and_the_hero(): void
    {
        foreach (['light', 'dark'] as $theme) {
            $page = ['theme' => $theme, 'layers' => [], 'raised' => false];
            $hero = ['theme' => $theme, 'layers' => ['.on-dark', '.glow-brass'], 'raised' => false];

            $this->assertGreaterThanOrEqual(self::UI_MINIMUM, $this->ratio($this->resolve('accent-line', $page), $this->resolve('surface', $page)), "{$theme}: brass on the page");
            $this->assertGreaterThanOrEqual(self::UI_MINIMUM, $this->ratio($this->resolve('accent-line', $hero), $this->resolve('surface', $hero)), "{$theme}: brass on the hero");
        }
    }

    #[Test]
    public function the_dark_theme_inverts_the_page_and_the_light_theme_is_ivory(): void
    {
        $light = ['theme' => 'light', 'layers' => [], 'raised' => false];
        $dark = ['theme' => 'dark', 'layers' => [], 'raised' => false];

        $this->assertSame(strtolower(Brand::IVORY), $this->hex($this->resolve('surface', $light)));
        $this->assertSame(strtolower(Brand::EVERGREEN), $this->hex($this->resolve('ink', $light)));
        $this->assertSame(strtolower(Brand::EVERGREEN), $this->hex($this->resolve('surface', $dark)));
        $this->assertSame(strtolower(Brand::CYPRESS), $this->hex($this->resolve('surface-raised', $dark)));
        $this->assertSame(strtolower(Brand::CHAMPAGNE), $this->hex($this->resolve('ink', $dark)));
        $this->assertSame(strtolower(Brand::BRONZE), $this->hex($this->resolve('accent-text', $light)));
        $this->assertSame(strtolower(Brand::BRASS), $this->hex($this->resolve('accent-text', $dark)));
        $this->assertSame(strtolower(Brand::BRASS), $this->hex($this->resolve('accent-line', $light)));
        $this->assertSame(strtolower(Brand::BRASS), $this->hex($this->resolve('accent-line', $dark)));
    }

    #[Test]
    public function every_token_mixes_only_brand_colours(): void
    {
        $palette = array_map('strtolower', Brand::colours());

        foreach (self::contexts() as $label => $context) {
            foreach ($this->tokenNames() as $token) {
                foreach ($this->sources($token, $context) as $hex) {
                    $this->assertContains($hex, $palette, "{$label}: --{$token} uses {$hex}");
                }
            }
        }
    }

    // ------------------------------------------------------------------ helpers

    /**
     * The surfaces an ink can sit on in a context, each as an opaque colour.
     *
     * @return array<string, array{float, float, float, float}>
     */
    private function surfacesFor(array $context): array
    {
        $names = $context['raised'] ? ['surface-raised', 'surface-panel'] : ['surface', 'surface-sunken'];
        $surfaces = [];

        foreach ($names as $name) {
            $surfaces[$name] = $this->resolve($name, $context);
            $surfaces["surface-field on {$name}"] = $this->over($this->resolve('surface-field', $context), $surfaces[$name]);
        }

        return $surfaces;
    }

    /**
     * @return list<string>
     */
    private function tokenNames(): array
    {
        $names = [];

        foreach ($this->blocks as $declarations) {
            foreach (array_keys($declarations) as $property) {
                if (str_starts_with($property, '--') && ! str_starts_with($property, '--brand-')) {
                    $names[] = substr($property, 2);
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * The declarations in force for a context, later layers overriding earlier ones.
     *
     * @return array<string, string>
     */
    private function declarations(array $context): array
    {
        $declarations = $this->blocks[':root'];

        foreach ($context['layers'] as $layer) {
            $declarations = [...$declarations, ...$this->blocks[$layer]];
        }

        if ($context['raised']) {
            $declarations = [...$declarations, ...$this->blocks['raised']];
        }

        return $declarations;
    }

    /**
     * A token as an sRGB colour with alpha, in the given context.
     *
     * @return array{float, float, float, float}
     */
    private function resolve(string $token, array $context): array
    {
        $declarations = $this->declarations($context);

        $this->assertArrayHasKey("--{$token}", $declarations, "--{$token} is not declared");

        return $this->evaluate($declarations["--{$token}"], $declarations, $context['theme']);
    }

    /**
     * @param  array<string, string>  $declarations
     * @return array{float, float, float, float}
     */
    private function evaluate(string $value, array $declarations, string $theme): array
    {
        $value = trim($value);

        if ($value === 'transparent') {
            return [0, 0, 0, 0];
        }

        if (preg_match('/^#([0-9a-f]{6})$/i', $value, $m)) {
            return [hexdec(substr($m[1], 0, 2)) / 255, hexdec(substr($m[1], 2, 2)) / 255, hexdec(substr($m[1], 4, 2)) / 255, 1];
        }

        if (preg_match('/^var\((--[a-z0-9-]+)\)$/i', $value, $m)) {
            $this->assertArrayHasKey($m[1], $declarations, "{$m[1]} is not declared");

            return $this->evaluate($declarations[$m[1]], $declarations, $theme);
        }

        if (preg_match('/^light-dark\((.*)\)$/is', $value, $m)) {
            $arguments = $this->splitArguments($m[1]);
            $this->assertCount(2, $arguments, "light-dark() takes two values: {$value}");

            return $this->evaluate($arguments[$theme === 'dark' ? 1 : 0], $declarations, $theme);
        }

        if (preg_match('/^color-mix\(\s*in\s+srgb\s*,(.*)\)$/is', $value, $m)) {
            $arguments = $this->splitArguments($m[1]);
            $this->assertCount(2, $arguments, "color-mix() takes two colours: {$value}");

            [$first, $firstShare] = $this->colourAndShare($arguments[0]);
            [$second, $secondShare] = $this->colourAndShare($arguments[1]);
            $firstShare ??= $secondShare === null ? 0.5 : 1 - $secondShare;
            $secondShare ??= 1 - $firstShare;

            return $this->mix($this->evaluate($first, $declarations, $theme), $firstShare, $this->evaluate($second, $declarations, $theme), $secondShare);
        }

        $this->fail("Cannot evaluate token value: {$value}");
    }

    /**
     * @return array{string, ?float}
     */
    private function colourAndShare(string $argument): array
    {
        if (preg_match('/^(.*?)\s+(\d+(?:\.\d+)?)%$/s', trim($argument), $m)) {
            return [trim($m[1]), (float) $m[2] / 100];
        }

        return [trim($argument), null];
    }

    /**
     * Split function arguments at top-level commas.
     *
     * @return list<string>
     */
    private function splitArguments(string $list): array
    {
        $arguments = [];
        $depth = 0;
        $current = '';

        foreach (str_split($list) as $char) {
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            if ($char === ',' && $depth === 0) {
                $arguments[] = trim($current);
                $current = '';

                continue;
            }

            $current .= $char;
        }

        $arguments[] = trim($current);

        return $arguments;
    }

    /**
     * color-mix() in sRGB with premultiplied alpha, as the specification defines it.
     *
     * @param  array{float, float, float, float}  $a
     * @param  array{float, float, float, float}  $b
     * @return array{float, float, float, float}
     */
    private function mix(array $a, float $shareA, array $b, float $shareB): array
    {
        $alpha = $a[3] * $shareA + $b[3] * $shareB;

        if ($alpha <= 0) {
            return [0, 0, 0, 0];
        }

        $channel = fn (int $i): float => ($a[$i] * $a[3] * $shareA + $b[$i] * $b[3] * $shareB) / $alpha;

        return [$channel(0), $channel(1), $channel(2), $alpha];
    }

    /**
     * Composite a colour over an opaque background.
     *
     * @param  array{float, float, float, float}  $foreground
     * @param  array{float, float, float, float}  $background
     * @return array{float, float, float, float}
     */
    private function over(array $foreground, array $background): array
    {
        $a = $foreground[3];

        return [
            $foreground[0] * $a + $background[0] * (1 - $a),
            $foreground[1] * $a + $background[1] * (1 - $a),
            $foreground[2] * $a + $background[2] * (1 - $a),
            1,
        ];
    }

    /**
     * WCAG contrast ratio of an ink (composited if translucent) over an opaque surface.
     *
     * @param  array{float, float, float, float}  $ink
     * @param  array{float, float, float, float}  $surface
     */
    private function ratio(array $ink, array $surface): float
    {
        $surface = $this->over($surface, [1, 1, 1, 1]);
        $ink = $this->over($ink, $surface);
        $l1 = $this->luminance($ink);
        $l2 = $this->luminance($surface);

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    /**
     * @param  array{float, float, float, float}  $colour
     */
    private function luminance(array $colour): float
    {
        $linear = static fn (float $c): float => $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $linear($colour[0]) + 0.7152 * $linear($colour[1]) + 0.0722 * $linear($colour[2]);
    }

    /**
     * @param  array{float, float, float, float}  $colour
     */
    private function hex(array $colour): string
    {
        return sprintf('#%02x%02x%02x', (int) round($colour[0] * 255), (int) round($colour[1] * 255), (int) round($colour[2] * 255));
    }

    /**
     * The brand hex values a token is built from in a context.
     *
     * @return list<string>
     */
    private function sources(string $token, array $context): array
    {
        $declarations = $this->declarations($context);
        $seen = [];
        $walk = function (string $value) use (&$walk, &$seen, $declarations, $context): void {
            if (preg_match('/^light-dark\((.*)\)$/is', trim($value), $m)) {
                $arguments = $this->splitArguments($m[1]);
                $walk($arguments[$context['theme'] === 'dark' ? 1 : 0]);

                return;
            }

            preg_match_all('/#[0-9a-f]{6}\b/i', $value, $hex);
            array_push($seen, ...array_map('strtolower', $hex[0]));

            preg_match_all('/var\((--[a-z0-9-]+)\)/i', $value, $vars);

            foreach ($vars[1] as $name) {
                $walk($declarations[$name] ?? '');
            }
        };

        $walk($declarations["--{$token}"]);

        return array_values(array_unique($seen));
    }

    /**
     * The custom-property declarations of each rule block, keyed by a short name.
     *
     * @return array<string, array<string, string>>
     */
    private function parseBlocks(string $css): array
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        $blocks = [];

        preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $rules, PREG_SET_ORDER);

        foreach ($rules as [, $selector, $body]) {
            $selector = trim(preg_replace('/\s+/', ' ', $selector));
            $key = match (true) {
                $selector === ':root' => ':root',
                $selector === '.on-dark' => '.on-dark',
                $selector === '.glow-brass' => '.glow-brass',
                str_starts_with($selector, '.card') => 'raised',
                default => null,
            };

            if ($key === null) {
                continue;
            }

            $declarations = [];

            foreach ($this->splitDeclarations($body) as $declaration) {
                [$property, $value] = array_map('trim', explode(':', $declaration, 2)) + [1 => ''];

                if ($property !== '') {
                    $declarations[$property] = preg_replace('/\s+/', ' ', $value);
                }
            }

            $blocks[$key] = [...($blocks[$key] ?? []), ...$declarations];
        }

        foreach ([':root', '.on-dark', '.glow-brass', 'raised'] as $required) {
            $this->assertArrayHasKey($required, $blocks, "tokens.css has no {$required} block");
        }

        return $blocks;
    }

    /**
     * Split a rule body at semicolons outside parentheses.
     *
     * @return list<string>
     */
    private function splitDeclarations(string $body): array
    {
        return array_filter(array_map('trim', $this->splitOn($body, ';')), static fn (string $d): bool => $d !== '');
    }

    /**
     * @return list<string>
     */
    private function splitOn(string $text, string $separator): array
    {
        $parts = [];
        $depth = 0;
        $current = '';

        foreach (str_split($text) as $char) {
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            if ($char === $separator && $depth === 0) {
                $parts[] = $current;
                $current = '';

                continue;
            }

            $current .= $char;
        }

        $parts[] = $current;

        return $parts;
    }
}
