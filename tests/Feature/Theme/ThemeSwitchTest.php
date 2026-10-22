<?php

namespace Tests\Feature\Theme;

use App\Constants\AdminNavigation;
use App\Constants\Theme;
use App\Constants\ThemeSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The theme route and how the stored choice reaches <html> on every layout.
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
    public function the_plain_form_redirects_back_with_the_cookie(): void
    {
        $this->from(route('home'))
            ->patch(route(ThemeSettings::ROUTE), [ThemeSettings::FIELD => Theme::Light->value])
            ->assertRedirect(route('home'))
            ->assertCookie(ThemeSettings::COOKIE, 'light', encrypted: false);
    }

    #[Test]
    public function an_unknown_theme_is_rejected(): void
    {
        $this->patchJson(route(ThemeSettings::ROUTE), [ThemeSettings::FIELD => 'sepia'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(ThemeSettings::FIELD);

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
    public function the_default_is_system_and_prints_no_forced_scheme(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-theme-preference="system"', false)
            ->assertDontSee('data-theme="', false)
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
            ->assertSee('data-theme-preference="dark" data-theme="dark"', false);
    }

    #[Test]
    public function a_bad_cookie_falls_back_to_system(): void
    {
        $this->withUnencryptedCookie(ThemeSettings::COOKIE, 'neon')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-theme-preference="system"', false);
    }

    #[Test]
    public function the_account_choice_wins_over_the_cookie_on_every_signed_in_layout(): void
    {
        $editor = User::factory()->editor()->darkTheme()->create();

        $this->actingAs($editor)
            ->withUnencryptedCookie(ThemeSettings::COOKIE, 'light')
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('data-theme-preference="dark" data-theme="dark"', false)
            ->assertSee('data-theme-switch', false);

        $this->actingAs($editor)
            ->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertOk()
            ->assertSee('data-theme-section', false)
            ->assertSee('value="dark" class="theme-option__input sr-only" checked', false)
            ->assertSee(route(ThemeSettings::ROUTE));
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
