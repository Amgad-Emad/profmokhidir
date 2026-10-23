<?php

namespace Tests\Feature\Theme;

use App\Constants\AdminNavigation;
use App\Constants\Theme;
use App\Constants\ThemeSettings;
use App\Models\User;
use App\Services\CommunityPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The theme route and how the stored choice reaches <html> and the toggle on every layout.
 */
class ThemeSwitchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_guest_choice_is_kept_in_a_plain_cookie(): void
    {
        $response = $this->patchJson(route(ThemeSettings::ROUTE), [ThemeSettings::FIELD => Theme::Dark->value]);

        $response->assertOk()->assertJson(['theme' => 'dark'])->assertCookie(ThemeSettings::COOKIE, 'dark', encrypted: false);

        $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === ThemeSettings::COOKIE);
        $this->assertFalse($cookie->isHttpOnly(), 'the inline head script must be able to read it');
        $this->assertSame('lax', $cookie->getSameSite());
    }

    #[Test]
    public function the_toggle_works_without_script_and_redirects_back_with_the_cookie(): void
    {
        $this->from(route('home'))
            ->patch(route(ThemeSettings::ROUTE), [ThemeSettings::FIELD => Theme::Light->value])
            ->assertRedirect(route('home'))
            ->assertCookie(ThemeSettings::COOKIE, 'light', encrypted: false);
    }

    #[Test]
    public function only_light_and_dark_are_accepted(): void
    {
        foreach (['sepia', ThemeSettings::LEGACY_SYSTEM] as $value) {
            $this->patchJson(route(ThemeSettings::ROUTE), [ThemeSettings::FIELD => $value])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(ThemeSettings::FIELD);
        }

        $this->patchJson(route(ThemeSettings::ROUTE), [])->assertUnprocessable();
    }

    #[Test]
    public function a_signed_in_user_keeps_the_choice_on_the_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson(route(ThemeSettings::ROUTE), [ThemeSettings::FIELD => Theme::Dark->value])
            ->assertOk();

        $this->assertSame(Theme::Dark, $user->fresh()->theme);
        $this->assertDatabaseHas('users', ['id' => $user->id, ThemeSettings::COLUMN => 'dark']);
    }

    #[Test]
    public function before_any_choice_the_device_decides_and_no_scheme_is_forced(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-theme="', false)
            ->assertSee('data-theme-toggle', false)
            ->assertSee('name="theme-color"', false)
            ->assertSee('media="(prefers-color-scheme: dark)"', false)
            ->assertSee('name="csrf-token"', false);
    }

    #[Test]
    public function a_guest_cookie_forces_the_scheme_on_the_public_layout(): void
    {
        $this->withUnencryptedCookie(ThemeSettings::COOKIE, 'dark')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('<html lang="en" data-theme="dark">', false)
            ->assertSee('aria-label="Switch to light theme" aria-pressed="true"', false);
    }

    #[Test]
    public function a_bad_or_retired_cookie_forces_nothing(): void
    {
        foreach (['neon', ThemeSettings::LEGACY_SYSTEM] as $value) {
            $this->withUnencryptedCookie(ThemeSettings::COOKIE, $value)
                ->get(route('home'))
                ->assertOk()
                ->assertDontSee('data-theme="', false);
        }
    }

    #[Test]
    public function the_account_choice_wins_over_the_cookie_on_every_signed_in_layout(): void
    {
        $editor = User::factory()->editor()->darkTheme()->create();

        $this->actingAs($editor)
            ->withUnencryptedCookie(ThemeSettings::COOKIE, 'light')
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('data-theme="dark"', false)
            ->assertSee('id="theme-switch-admin" name="theme" value="light"', false);
    }

    #[Test]
    public function the_account_page_offers_the_same_toggle_instead_of_three_choices(): void
    {
        $editor = User::factory()->editor()->darkTheme()->create();

        $this->actingAs($editor)
            ->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertOk()
            ->assertSee('data-theme-section', false)
            ->assertSee(ThemeSettings::ACCOUNT_INTRO)
            ->assertSee('id="theme-switch-account" name="theme" value="light" class="theme-toggle__button" aria-label="Switch to light theme" aria-pressed="true"', false)
            ->assertDontSee('theme-option', false)
            ->assertDontSee('Save appearance')
            ->assertDontSee('value="system"', false);
    }

    #[Test]
    public function the_toggle_is_in_the_public_header_the_chat_header_the_sign_in_pages_and_the_admin_bar(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('id="theme-switch-masthead"', false);
        $this->get(route('login'))->assertOk()->assertSee('id="theme-switch-auth"', false);
        $this->get(route('password.request'))->assertOk()->assertSee('id="theme-switch-auth"', false);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('id="theme-switch-admin"', false);

        $this->actingAs(User::factory()->member()->create())
            ->get(route(CommunityPageService::ROUTE_CHAT))
            ->assertOk()
            ->assertSee('id="theme-switch-chat"', false);
    }

    #[Test]
    public function the_sign_in_and_error_screens_carry_the_theme_too(): void
    {
        $this->withUnencryptedCookie(ThemeSettings::COOKIE, 'light')
            ->get(route('login'))
            ->assertOk()
            ->assertSee('data-theme="light"', false);

        $this->withUnencryptedCookie(ThemeSettings::COOKIE, 'dark')
            ->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('data-theme="dark"', false);
    }
}
