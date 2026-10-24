<?php

namespace Tests\Unit\Design;

use App\Constants\Brand;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Unit\Design\Concerns\ResolvesDesignTokens;

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
    use ResolvesDesignTokens;

    private const float TEXT_MINIMUM = 4.5;

    private const float UI_MINIMUM = 3.0;

    /** Inks that carry text of any size. */
    private const array TEXT_INKS = ['ink', 'ink-strong', 'ink-muted', 'ink-quiet', 'accent-text', 'danger-tone', 'pending-tone', 'success-tone', 'neutral-tone'];

    /** Inks that sit inside a form field (its text, placeholder and hint). */
    private const array FIELD_INKS = ['ink', 'ink-strong', 'ink-muted', 'ink-quiet'];

    /** Tokens that mark a user-interface boundary (WCAG 1.4.11). */
    private const array UI_INKS = ['focus', 'rule-strong'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadTokens();
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

    /**
     * Black is accepted only as a mix partner that deepens evergreen (the solid button's hover).
     */
    #[Test]
    public function every_token_mixes_only_brand_colours(): void
    {
        $palette = array_map('strtolower', [...Brand::colours(), '#000000']);

        foreach (self::contexts() as $label => $context) {
            foreach ($this->tokenNames() as $token) {
                foreach ($this->sources($token, $context) as $hex) {
                    $this->assertContains($hex, $palette, "{$label}: --{$token} uses {$hex}");

                    if ($hex === '#000000') {
                        $this->assertMatchesRegularExpression('/color-mix\\(in srgb, var\\(--brand-evergreen\\) \\d+%, #000000\\)/', $this->rawValue($token, $context), "{$label}: --{$token} may only deepen evergreen with black");
                    }
                }
            }
        }
    }
}
