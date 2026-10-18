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

    protected function setUp(): void
    {
        parent::setUp();

        $this->css = file_get_contents(resource_path('css/app.css'));
    }

    #[Test]
    public function tailwinds_default_palette_is_removed(): void
    {
        $this->assertStringContainsString('--color-*: initial;', $this->css);
    }

    #[Test]
    public function every_brand_colour_is_a_theme_token_and_no_other_hex_colour_appears(): void
    {
        foreach (Brand::colours() as $name => $hex) {
            $this->assertMatchesRegularExpression(
                sprintf('/--color-%s:\s*%s;/i', $name, preg_quote($hex, '/')),
                $this->css,
                "Token --color-{$name} must be {$hex}",
            );
        }

        preg_match_all('/#[0-9a-f]{6}\b/i', $this->css, $matches);
        $allowed = array_map('strtoupper', [...Brand::colours(), ...Brand::permittedExtras()]);

        foreach (array_unique(array_map('strtoupper', $matches[0])) as $hex) {
            $this->assertContains($hex, $allowed, "{$hex} is not a brand colour");
        }
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
