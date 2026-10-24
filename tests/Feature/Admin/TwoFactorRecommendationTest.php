<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\AdminUx;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Two-factor authentication is recommended for every role, required for none:
 * admins open the dashboard without it and see a dismissible card instead.
 */
class TwoFactorRecommendationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admins_without_two_factor_open_the_dashboard_and_see_the_card(): void
    {
        $this->actingAs(User::factory()->admin()->withoutTwoFactor()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('data-two-factor-card', false)
            ->assertSee(AdminUx::TWO_FACTOR_REMINDER_TITLE)
            ->assertSee(route(AdminNavigation::ACCOUNT_ROUTE).'#two-factor', false)
            ->assertSee(route(AdminUx::TWO_FACTOR_REMINDER_ROUTE), false);
    }

    #[Test]
    public function admins_with_pending_two_factor_are_not_blocked(): void
    {
        $this->actingAs(User::factory()->admin()->withPendingTwoFactor()->create())
            ->get(route(AdminNavigation::routeFor(AdminNavigation::USERS)))
            ->assertOk();
    }

    #[Test]
    public function the_card_is_not_shown_to_admins_with_two_factor_or_to_editors(): void
    {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertDontSee('data-two-factor-card', false);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertDontSee('data-two-factor-card', false);
    }

    #[Test]
    public function dismissing_the_card_is_remembered_for_that_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $colleague = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route(AdminUx::TWO_FACTOR_REMINDER_ROUTE))
            ->assertRedirect(route(AdminNavigation::DASHBOARD_ROUTE));

        $this->assertNotNull($admin->fresh()->two_factor_reminder_dismissed_at);
        $this->actingAs($admin->fresh())->get(route(AdminNavigation::DASHBOARD_ROUTE))->assertDontSee('data-two-factor-card', false);
        $this->actingAs($colleague)->get(route(AdminNavigation::DASHBOARD_ROUTE))->assertSee('data-two-factor-card', false);
    }

    #[Test]
    public function dismissing_needs_a_signed_in_staff_member_and_a_csrf_token(): void
    {
        $this->post(route(AdminUx::TWO_FACTOR_REMINDER_ROUTE))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->member()->create())
            ->post(route(AdminUx::TWO_FACTOR_REMINDER_ROUTE))
            ->assertForbidden();
    }

    #[Test]
    public function the_account_page_recommends_two_factor_to_admins_without_requiring_it(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertOk()
            ->assertSee('Recommended for administrator accounts')
            ->assertDontSee('required for administrator accounts')
            ->assertSee('data-two-factor="off"', false)
            ->assertSee(route('two-factor.enable'));

        $this->actingAs(User::factory()->editor()->create())
            ->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertOk()
            ->assertDontSee('Recommended for administrator accounts')
            ->assertSee(route('two-factor.enable'));
    }
}
