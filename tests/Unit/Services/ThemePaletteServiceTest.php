<?php

namespace Tests\Unit\Services;

use App\Constants\Theme;
use App\Constants\ThemeContrast;
use App\Constants\ThemePresets;
use App\Constants\ThemeTokens;
use App\Services\ThemePaletteService;
use App\Support\Colour;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The palette arithmetic: the brand resolves to its own values, unset tokens
 * derive from the Basic colours (hover and pressed shades, tints, translucent
 * rules, inks that keep their contrast), every suggested template passes WCAG
 * 2.2 AA in both modes, and the CSS a theme prints.
 */
class ThemePaletteServiceTest extends TestCase
{
    private ThemePaletteService $palette;

    protected function setUp(): void
    {
        parent::setUp();

        $this->palette = new ThemePaletteService;
    }

    #[Test]
    public function the_brand_resolves_to_exactly_its_own_values(): void
    {
        $brand = ThemePresets::get(ThemePresets::BRAND);
        $resolved = $this->palette->resolve($brand['light'], $brand['dark']);

        $this->assertSame(ThemeTokens::defaults(Theme::Light), $resolved['light']);
        $this->assertSame(ThemeTokens::defaults(Theme::Dark), $resolved['dark']);
    }

    #[Test]
    public function an_empty_theme_falls_back_to_the_brand_basic_colours(): void
    {
        $resolved = $this->palette->resolve([], []);

        foreach (ThemeTokens::basic() as $token) {
            $this->assertSame(ThemeTokens::defaults(Theme::Light)[$token], $resolved['light'][$token]);
            $this->assertSame(ThemeTokens::defaults(Theme::Dark)[$token], $resolved['dark'][$token]);
        }

        $this->assertSame([], $this->palette->failures($resolved), 'the derived brand passes every pair');
    }

    #[Test]
    public function derived_shades_follow_the_basic_colours(): void
    {
        $resolved = $this->palette->resolve(['primary' => '#14213d', 'surface' => '#f4efe3', 'accent-line' => '#a5813f', 'ink' => '#14213d'], []);
        $light = $resolved['light'];

        // The solid button is the Primary colour in light; hover and press deepen it with black.
        $this->assertSame('#14213d', $light['fill']);
        $this->assertSame(Colour::mix('#14213d', 78, Colour::BLACK), $light['fill-deep']);
        $this->assertSame(Colour::mix('#14213d', 62, Colour::BLACK), $light['fill-pressed']);
        // Translucent rules and the glow are the accent at reduced opacity.
        $this->assertSame(Colour::alpha('#a5813f', 35), $light['band-rule']);
        $this->assertSame(Colour::alpha('#a5813f', 22), $light['accent-glow']);
        $this->assertSame(Colour::alpha('#14213d', 88), $light['band-glass']);
        // Danger hover mixes the danger red into the primary colour.
        $this->assertSame(Colour::mix($light['danger'], 84, '#14213d'), $light['danger-surface-hover']);
        // Labels darken the accent until they read at 4.5:1 on the page.
        $this->assertGreaterThanOrEqual(4.5, Colour::contrast($light['accent-text'], $light['surface']));
        $this->assertNotSame('#a5813f', $light['accent-text']);
        // Links and body text follow Text; the band takes the Primary colour.
        $this->assertSame('#14213d', $light['link']);
        $this->assertSame('#14213d', $light['ink-body']);
        $this->assertSame('#14213d', $light['band-surface']);
    }

    #[Test]
    public function editing_one_basic_colour_moves_every_token_that_follows_it(): void
    {
        $before = $this->palette->resolve(['primary' => '#14213d'], []);
        $after = $this->palette->resolve(['primary' => '#4a1621'], []);

        foreach (['fill', 'fill-deep', 'fill-pressed', 'band-surface', 'band-glass', 'footer-surface', 'surface-overlay', 'duotone-shadow'] as $token) {
            $this->assertNotSame($before['light'][$token], $after['light'][$token], $token);
        }

        $this->assertSame($before['light']['surface'], $after['light']['surface']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function presets(): array
    {
        return array_combine(ThemePresets::slugs(), array_map(static fn (string $slug): array => [$slug], ThemePresets::slugs()));
    }

    #[Test]
    #[DataProvider('presets')]
    public function every_template_passes_wcag_2_2_aa_in_both_modes(string $slug): void
    {
        $preset = ThemePresets::get($slug);
        $resolved = $this->palette->resolve($preset['light'], $preset['dark']);
        $checks = $this->palette->check($resolved);

        $this->assertCount(count(ThemeContrast::pairs()) * 2, $checks);
        $this->assertSame([], array_map(
            static fn (array $f): string => "{$f['mode']} {$f['id']}: {$f['ratio']} < {$f['minimum']}",
            $this->palette->failures($resolved),
        ));
    }

    #[Test]
    public function a_failing_pair_is_reported_with_a_passing_suggestion(): void
    {
        $resolved = $this->palette->resolve(['ink' => '#c9c2b0'], []);
        $failures = $this->palette->failures($resolved);
        $body = collect($failures)->firstWhere('id', 'body');

        $this->assertNotNull($body);
        $this->assertTrue($body['critical']);
        $this->assertSame('ink-body', $body['token']);
        $this->assertGreaterThanOrEqual(4.5, Colour::contrast($body['suggestion'], $resolved['light']['surface']));
        $this->assertNotEmpty($this->palette->failures($resolved, criticalOnly: true));
    }

    #[Test]
    public function the_css_redeclares_every_token_as_a_light_dark_pair(): void
    {
        $preset = ThemePresets::get(ThemePresets::OXFORD);
        $css = $this->palette->css($this->palette->resolve($preset['light'], $preset['dark']));

        $this->assertStringStartsWith(':root:root{', $css);
        $this->assertStringContainsString('--surface:light-dark(#f4efe3,#0f1a30)', $css);

        foreach (ThemeTokens::keys() as $token) {
            $this->assertMatchesRegularExpression('/--'.preg_quote($token, '/').':(?:light-dark\(#[0-9a-f]{6,8},#[0-9a-f]{6,8}\)|#[0-9a-f]{6,8})[;}]/', $css, $token);
        }

        $this->assertDoesNotMatchRegularExpression('/[<>"\'\\\\]|url\(/', $css, 'only hex values and token names');
    }

    #[Test]
    public function sanitise_drops_unknown_tokens_and_invalid_colours(): void
    {
        $this->assertSame(['surface' => '#ffffff'], $this->palette->sanitise([
            'surface' => '#FFF', 'sparkle' => '#000000', 'ink' => 'red', 'primary' => ['#000000'], 'link' => '#12345',
        ]));
    }

    #[Test]
    public function the_mark_ring_keeps_brass_unless_a_brass_like_tone_of_the_palette_is_chosen(): void
    {
        $this->assertSame('#a8864f', $this->palette->resolve(['mark-ring' => '#ff0000'], [])['light']['mark-ring']);

        $accent = '#a5813f';
        $resolved = $this->palette->resolve(['accent-line' => $accent, 'mark-ring' => $accent], []);
        $this->assertSame($accent, $resolved['light']['mark-ring']);
        $this->assertContains($accent, $this->palette->markRingOptions($resolved['light']));
        $this->assertNotContains($resolved['light']['ink'], $this->palette->markRingOptions($resolved['light']));
    }

    #[Test]
    public function compact_drops_chosen_colours_that_equal_their_derivation(): void
    {
        $derived = $this->palette->resolve(['primary' => '#14213d'], []);
        $compact = $this->palette->compact(['primary' => '#14213d', 'fill' => $derived['light']['fill'], 'link' => '#000000'], []);

        $this->assertSame(['primary' => '#14213d', 'link' => '#000000'], $compact['light']);
    }

    #[Test]
    public function quick_picks_are_the_opaque_colours_of_the_palette_basic_first(): void
    {
        $picks = $this->palette->quickPicks(ThemeTokens::defaults(Theme::Light));

        $this->assertSame(['#f3f1ea', '#e7e5dc', '#13221c', '#a8864f', '#9b2c2c'], array_slice($picks, 0, 5));
        $this->assertSame(array_values(array_unique($picks)), $picks);
        $this->assertSame([], array_filter($picks, Colour::isTranslucent(...)));
    }
}
