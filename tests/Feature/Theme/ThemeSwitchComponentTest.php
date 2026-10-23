<?php

namespace Tests\Feature\Theme;

use App\Constants\ThemeSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The <x-theme.switch> toggle and the <x-theme.head> plumbing.
 */
class ThemeSwitchComponentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_toggle_is_one_pressed_button_with_no_menu(): void
    {
        $html = Blade::render('<x-theme.switch id="t" />');

        $this->assertStringContainsString('<form novalidate method="POST" action="'.route(ThemeSettings::ROUTE).'" class="theme-toggle" data-theme-toggle data-theme-label="Switch to %s theme" data-theme-showing="light">', $html);
        $this->assertStringContainsString('name="_method" value="PATCH"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('<button type="submit" id="t" name="theme" value="dark" class="theme-toggle__button" aria-label="Switch to dark theme" aria-pressed="false" data-theme-toggle-button>', $html);
        $this->assertSame(1, substr_count($html, '<button'));
        $this->assertStringContainsString('theme-toggle__icon--moon', $html);
        $this->assertStringContainsString('theme-toggle__icon--sun', $html);

        foreach (['role="menu"', 'menuitemradio', 'aria-haspopup', 'aria-expanded', 'System'] as $gone) {
            $this->assertStringNotContainsString($gone, $html);
        }
    }

    #[Test]
    public function in_the_dark_theme_it_offers_the_light_one_and_is_pressed(): void
    {
        $this->actingAs(User::factory()->darkTheme()->create());

        $html = Blade::render('<x-theme.switch id="t" />');

        $this->assertStringContainsString('data-theme-showing="dark"', $html);
        $this->assertStringContainsString('name="theme" value="light" class="theme-toggle__button" aria-label="Switch to light theme" aria-pressed="true"', $html);
    }

    #[Test]
    public function the_head_prints_both_scheme_colours_and_the_inline_script(): void
    {
        $html = Blade::render('<x-theme.head light="evergreen" dark="cypress" />');

        $this->assertStringContainsString('name="csrf-token"', $html);
        $this->assertStringContainsString('content="#13221C" media="(prefers-color-scheme: light)" data-light="#13221C" data-dark="#2F4A3E"', $html);
        $this->assertStringContainsString('content="#2F4A3E" media="(prefers-color-scheme: dark)"', $html);
        $this->assertStringContainsString('<script data-theme-boot>', $html);
        $this->assertStringContainsString('localStorage', $html);
        $this->assertStringContainsString("'".ThemeSettings::STORAGE_KEY."'", $html);
        // A device still holding the retired "system" pins the theme it resolves to and flags it for saving.
        $this->assertStringContainsString("legacy = '".ThemeSettings::LEGACY_SYSTEM."'", $html);
        $this->assertStringContainsString("'".ThemeSettings::LEGACY_ATTRIBUTE."'", $html);
        $this->assertStringContainsString('prefers-color-scheme: dark', $html);
    }

    #[Test]
    public function a_forced_scheme_tints_the_browser_chrome_with_that_scheme_in_both_metas(): void
    {
        $this->actingAs(User::factory()->darkTheme()->create());

        $html = Blade::render('<x-theme.head light="evergreen" dark="cypress" />');

        $this->assertSame(2, substr_count($html, 'content="#2F4A3E"'));
        $this->assertStringNotContainsString('content="#13221C"', $html);
    }

    #[Test]
    public function the_theme_script_is_part_of_every_bundle(): void
    {
        foreach (['app', 'admin', 'chat'] as $entry) {
            $this->assertStringContainsString("import './theme';", file_get_contents(resource_path("js/{$entry}.js")), "{$entry}.js");
        }

        $js = file_get_contents(resource_path('js/theme.js'));
        $this->assertStringContainsString('prefers-reduced-motion', $js);
        $this->assertStringContainsString("'data-theme-transition'", $js);
        $this->assertStringContainsString("'".ThemeSettings::LEGACY_ATTRIBUTE."'", $js);
        $this->assertStringContainsString("'".ThemeSettings::SWITCH_LABEL."'", $js);
        $this->assertStringContainsString('aria-pressed', $js);
        $this->assertStringContainsString('theme-color', $js);
        $this->assertStringNotContainsString('system', strtolower(preg_replace('/\/\*.*?\*\/|\/\/[^\n]*/s', '', $js)), 'no System option is left in the code');
    }
}
