<?php

namespace Tests\Unit\Design;

use App\Constants\Brand;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the hand-written design system files against drifting from the brand rules.
 */
class BrandStylesheetTest extends TestCase
{
    private string $css;

    private string $tokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->css = file_get_contents(resource_path('css/app.css'));
        $this->tokens = file_get_contents(resource_path('css/tokens.css'));
    }

    #[Test]
    public function tailwinds_default_palette_is_removed_and_the_token_file_is_imported(): void
    {
        $this->assertStringContainsString('--color-*: initial;', $this->tokens);
        $this->assertStringContainsString("@import './tokens.css';", $this->css);
    }

    #[Test]
    public function every_brand_colour_is_a_palette_variable_and_no_other_hex_colour_appears(): void
    {
        foreach (Brand::colours() as $name => $hex) {
            $this->assertMatchesRegularExpression(
                sprintf('/--brand-%s:\s*%s;/i', $name, preg_quote($hex, '/')),
                $this->tokens,
                "Palette variable --brand-{$name} must be {$hex}",
            );
        }

        preg_match_all('/#[0-9a-f]{6}\b/i', $this->tokens, $matches);
        $allowed = array_map('strtoupper', [...Brand::colours(), ...Brand::permittedExtras()]);

        foreach (array_unique(array_map('strtoupper', $matches[0])) as $hex) {
            $this->assertContains($hex, $allowed, "{$hex} is not a brand colour");
        }

        $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}\b/i', preg_replace('/url\([^)]*\)/', '', $this->css), 'app.css names no colour: it uses the tokens');
    }

    #[Test]
    public function the_semantic_tokens_exist_in_both_themes_and_as_utilities(): void
    {
        foreach (['surface', 'surface-raised', 'surface-sunken', 'ink', 'ink-muted', 'ink-quiet', 'rule', 'rule-strong', 'accent-line', 'accent-text', 'focus', 'danger-tone', 'success-tone', 'pending-tone', 'selection'] as $token) {
            $this->assertMatchesRegularExpression('/^\s*--'.preg_quote($token, '/').':/m', $this->tokens, "--{$token} is declared");
            $this->assertStringContainsString("--color-{$token}: var(--{$token});", $this->tokens, "{$token} is a Tailwind colour utility");
        }

        $this->assertStringContainsString('light-dark(', $this->tokens);
        $this->assertStringContainsString("color-scheme: light dark;", $this->tokens);
        $this->assertStringContainsString(":root[data-theme='dark']", $this->tokens);
        $this->assertStringContainsString('.on-dark {', $this->tokens);
    }

    #[Test]
    public function it_defines_the_brand_fonts_type_scale_and_components(): void
    {
        foreach (['--font-display', '--font-sans', '--font-mono'] as $token) {
            $this->assertStringContainsString($token.':', $this->css);
        }

        foreach ([
            '.t-display', '.t-heading', '.t-quote', '.t-body', '.t-label',
            '.btn-pill-filled', '.btn-pill-outline', '.card', '.rule-brass', '.glow-brass',
            '.photo-portrait', '.photo-archive', '.glass-header',
        ] as $selector) {
            $this->assertMatchesRegularExpression('/'.preg_quote($selector, '/').'[\s,{]/', $this->css, "{$selector} is missing");
        }

        $this->assertStringContainsString('cubic-bezier(0.16, 1, 0.3, 1)', $this->css);
        $this->assertStringContainsString('prefers-reduced-motion', $this->css);
        $this->assertStringContainsString("filter: url('#duotone-archive')", $this->css);
    }

    #[Test]
    public function the_motion_script_exists_and_respects_reduced_motion_without_a_library(): void
    {
        $js = file_get_contents(resource_path('js/motion.js'));

        $this->assertStringContainsString('prefers-reduced-motion', $js);
        $this->assertStringContainsString('IntersectionObserver', $js);
        $this->assertStringContainsString('requestAnimationFrame', $js);
        $this->assertStringContainsString('{ passive: true }', $js);
        $this->assertDoesNotMatchRegularExpression('/^import\s+[^\n]*from\s+[\'"](gsap|framer-motion|motion|animejs|anime)/m', $js);

        $this->assertStringContainsString("import './motion'", file_get_contents(resource_path('js/app.js')));
    }
}
