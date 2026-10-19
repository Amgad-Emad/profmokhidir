<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\AuthStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TwoFactorRequirementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admins_without_two_factor_are_sent_to_the_account_page(): void
    {
        $this->actingAs(User::factory()->admin()->withoutTwoFactor()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertRedirect(route(AdminNavigation::ACCOUNT_ROUTE).'#two-factor')
            ->assertSessionHas('status', AuthStatus::TWO_FACTOR_REQUIRED);
    }

    #[Test]
    public function admins_with_unconfirmed_two_factor_are_still_blocked(): void
    {
        $this->actingAs(User::factory()->admin()->withPendingTwoFactor()->create())
            ->get(route(AdminNavigation::routeFor(AdminNavigation::USERS)))
            ->assertRedirect(route(AdminNavigation::ACCOUNT_ROUTE).'#two-factor');
    }

    #[Test]
    public function admins_with_confirmed_two_factor_and_editors_without_it_may_enter(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk();

        $this->actingAs(User::factory()->editor()->withoutTwoFactor()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk();
    }

    #[Test]
    public function the_account_page_explains_the_requirement_to_a_blocked_admin(): void
    {
        $this->actingAs(User::factory()->admin()->withoutTwoFactor()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE));

        $this->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertOk()
            ->assertSee('data-status="'.AuthStatus::TWO_FACTOR_REQUIRED.'"', false)
            ->assertSee('Administrator accounts must use two-factor authentication')
            ->assertSee('data-two-factor="off"', false)
            ->assertSee(route('two-factor.enable'));
    }
}
