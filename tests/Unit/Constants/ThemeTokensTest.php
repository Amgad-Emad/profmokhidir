<?php

namespace Tests\Unit\Constants;

use App\Constants\Theme;
use App\Constants\ThemePresets;
use App\Constants\ThemeTokens;
use App\Services\ThemePaletteService;
use App\Support\Colour;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Unit\Design\Concerns\ResolvesDesignTokens;

/**
 * Token completeness: every theme token is declared in tokens.css at its brand
 * value and resolves in every preset; every colour token the stylesheets read
 * is a theme token, an alias of theme tokens or a fixed brand token of the mark.
 */
class ThemeTokensTest extends TestCase
{
    use ResolvesDesignTokens;

    private const array COLOUR_PROPERTIES = ['color', 'background', 'background-color', 'border', 'border-color', 'outline', 'outline-color', 'fill', 'stroke', 'text-decoration-color', 'box-shadow', 'caret-color', 'accent-color',
        'border-top', 'border-bottom', 'border-left', 'border-right', 'border-block', 'border-inline', 'border-top-color', 'border-bottom-color', 'border-inline-start', 'border-inline-end', 'border-inline-start-color', 'border-block-start', 'border-block-end', 'column-rule'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadTokens();
    }

    #[Test]
    public function the_groups_hold_every_token_and_basic_is_the_six_shown_to_everyone(): void
    {
        $this->assertSame(['surface', 'surface-raised', 'primary', 'accent-line', 'ink', 'danger'], ThemeTokens::basic());

        foreach (ThemeTokens::all() as $token => $definition) {
            $this->assertArrayHasKey($definition['group'], ThemeTokens::groups(), $token);
            $this->assertNotSame('', trim($definition['label']), $token);
            $this->assertNotSame('', trim($definition['description']), $token);
            $this->assertTrue(Colour::isValid($definition['light']) && Colour::isValid($definition['dark']), $token);
        }

        foreach (['Heading text', 'Body text', 'Muted text', 'Quiet text', 'Label text', 'Link text', 'Link hover', 'Text on primary', 'Text on accent', 'Text on danger', 'Placeholder text', 'Selection text', 'Selection background'] as $label) {
            $this->assertContains($label, array_column(ThemeTokens::all(), 'label'), "the font colour \"{$label}\" is editable");
        }
    }

    #[Test]
    public function every_theme_token_is_declared_in_tokens_css_at_its_brand_value(): void
    {
        foreach (Theme::cases() as $mode) {
            $context = ['theme' => $mode->value, 'layers' => [], 'raised' => false];

            foreach (ThemeTokens::defaults($mode) as $token => $hex) {
                $this->assertArrayHasKey("--{$token}", $this->blocks[':root'], "--{$token} is not declared in tokens.css");
                $css = $this->resolve($token, $context);
                $this->assertSame(
                    Colour::normalise($hex),
                    Colour::hex($css),
                    "{$mode->value}: --{$token} in tokens.css is not the brand value of ThemeTokens",
                );
            }
        }
    }

    #[Test]
    public function every_token_resolves_in_every_preset(): void
    {
        $palette = app(ThemePaletteService::class);

        foreach (ThemePresets::all() as $slug => $preset) {
            $resolved = $palette->resolve($preset['light'], $preset['dark']);

            foreach (Theme::cases() as $mode) {
                $this->assertSame(ThemeTokens::keys(), array_keys($resolved[$mode->value]), $slug);
                $this->assertSame([], array_filter($resolved[$mode->value], static fn (string $value): bool => ! Colour::isValid($value)), $slug);
            }
        }
    }

    #[Test]
    public function the_root_colour_tokens_are_theme_tokens_aliases_or_fixed_brand_tokens(): void
    {
        $known = [...ThemeTokens::keys(), ...array_keys(ThemeTokens::aliases()), ...ThemeTokens::fixed()];

        foreach ($this->blocks[':root'] as $property => $value) {
            if (! str_starts_with($property, '--') || preg_match('/^--(?:brand|functional|color)-/', $property)) {
                continue;
            }

            if (preg_match('/^(?:light-dark|color-mix|var)\(|^#/', $value) && ! str_contains($value, 'var(--font') ) {
                $this->assertContains(substr($property, 2), $known, "{$property} is a colour token missing from ThemeTokens");
            }
        }

        foreach (ThemeTokens::aliases() as $alias => $source) {
            $this->assertStringNotContainsString('--brand-', $this->blocks[':root']["--{$alias}"], "--{$alias} must derive from theme tokens");
            $this->assertStringContainsString("var(--{$source})", $this->blocks[':root']["--{$alias}"]);
        }
    }

    #[Test]
    public function the_regions_read_theme_tokens_only(): void
    {
        foreach (['.on-dark', '.glow-brass', 'raised'] as $region) {
            foreach ($this->blocks[$region] as $property => $value) {
                $this->assertStringNotContainsString('--brand-', $value, "{$region} {$property} reads a brand colour: a theme could not reach it");
                $this->assertStringNotContainsString('--functional-', $value, "{$region} {$property}");
            }
        }
    }

    #[Test]
    public function every_colour_variable_the_stylesheets_read_is_declared(): void
    {
        $declared = [];
        $files = File::glob(resource_path('css/*.css'));

        foreach ($files as $file) {
            preg_match_all('/(--[a-z0-9-]+)\s*:/i', (string) file_get_contents($file), $m);
            array_push($declared, ...$m[1]);
        }

        // Set at runtime from the theme being shown (inline styles on swatches and miniatures) or by scripts.
        $runtime = '/^--(?:mini|swatch|sample)-?|^--(?:progress|w|i|share|span|strength|reading-progress|line-progress|rise-delay|mega-rise|inline-sign|masthead-|admin-topbar)/';
        $missing = [];

        foreach ($files as $file) {
            $css = preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents($file));
            preg_match_all('/(?<![\w-])('.implode('|', array_map('preg_quote', self::COLOUR_PROPERTIES)).')\s*:\s*([^;{}]+);/', $css, $rules, PREG_SET_ORDER);

            foreach ($rules as [, , $value]) {
                preg_match_all('/var\((--[a-z0-9-]+)/i', $value, $vars);

                foreach ($vars[1] as $variable) {
                    if (! in_array($variable, $declared, true) && ! preg_match($runtime, $variable)) {
                        $missing[] = basename($file).': '.$variable;
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)));
    }
}
