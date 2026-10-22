<?php

namespace Tests\Feature\Theme;

use App\Constants\Theme;
use App\Constants\ThemeSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The <x-theme.switch> control and the <x-theme.head> plumbing.
 */
class ThemeSwitchComponentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_control_is_a_menu_button_with_three_radio_items(): void
    {
        $html = Blade::render('<x-theme.switch id="t" />');

        $this->assertStringContainsString('data-theme-switch', $html);
        $this->assertStringContainsString('data-theme-endpoint="'.route(ThemeSettings::ROUTE).'"', $html);
        $this->assertStringContainsString('aria-haspopup="menu" aria-expanded="false" aria-controls="t-menu"', $html);
        $this->assertStringContainsString(ThemeSettings::CONTROL_LABEL, $html);
        $this->assertStringContainsString('theme-switch__icon--light', $html);
        $this->assertStringContainsString('theme-switch__icon--dark', $html);
        $this->assertStringContainsString('role="menu" aria-labelledby="t"', $html);
        $this->assertSame(3, substr_count($html, 'role="menuitemradio"'));

        foreach (Theme::cases() as $theme) {
            $this->assertStringContainsString('data-theme-option="'.$theme->value.'"', $html);
            $this->assertStringContainsString($theme->label(), $html);
            $this->assertStringContainsString($theme->description(), $html);
        }

        $this->assertStringContainsString('aria-checked="true" tabindex="-1" data-theme-option="system"', $html);
    }

    #[Test]
    public function the_current_choice_is_checked(): void
    {
        $this->actingAs(User::factory()->darkTheme()->create());

        $html = Blade::render('<x-theme.switch id="t" />');

        $this->assertStringContainsString('aria-checked="true" tabindex="-1" data-theme-option="dark"', $html);
        $this->assertStringContainsString('aria-checked="false" tabindex="-1" data-theme-option="system"', $html);
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
        $this->assertStringContainsString('theme-color', $js);
        $this->assertStringContainsString('menuitemradio', Blade::render('<x-theme.switch />'));
    }
}
