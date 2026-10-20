<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * /admin is guarded by Spatie's role middleware (admin|editor); individual
 * pages add Spatie's permission middleware.
 */
class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_admin_group_uses_the_spatie_role_middleware(): void
    {
        $middleware = app('router')->getRoutes()->getByName(AdminNavigation::DASHBOARD_ROUTE)->gatherMiddleware();

        $this->assertContains('auth', $middleware);
        $this->assertContains('verified', $middleware);
        $this->assertContains('role:admin|editor', $middleware);
        $this->assertContains('two-factor', $middleware);
    }

    #[Test]
    public function guests_are_sent_to_login_and_non_staff_are_forbidden(): void
    {
        $this->get(route(AdminNavigation::DASHBOARD_ROUTE))->assertRedirect(route('login'));

        foreach ([User::factory()->member(), User::factory()->applicant(), User::factory()] as $factory) {
            $this->actingAs($factory->create())
                ->get(route(AdminNavigation::DASHBOARD_ROUTE))
                ->assertForbidden();
        }
    }

    #[Test]
    public function every_navigation_item_has_its_own_screen_and_enforces_its_permission(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        $this->assertSame([], AdminNavigation::placeholders());

        foreach (AdminNavigation::items() as $key => $item) {
            if ($key === AdminNavigation::ACCOUNT) {
                continue;
            }

            $this->actingAs($admin)->get(route($item['route']))->assertOk()->assertSee($item['label']);

            $response = $this->actingAs($editor)->get(route($item['route']));

            if ($editor->can($item['permission'])) {
                $response->assertOk();
            } else {
                $response->assertForbidden();
            }
        }
    }

    #[Test]
    public function editors_cannot_open_admin_only_pages(): void
    {
        $editor = User::factory()->editor()->create();

        foreach ([AdminNavigation::USERS, AdminNavigation::SITE_SETTINGS, AdminNavigation::APPLICATIONS, AdminNavigation::MEMBERS, AdminNavigation::CHAT_REPORTS] as $key) {
            $this->actingAs($editor)->get(route(AdminNavigation::routeFor($key)))->assertForbidden();
        }

        $this->actingAs($editor)->get(route(AdminNavigation::routeFor(AdminNavigation::TRIBUTES)))->assertOk();
        $this->actingAs($editor)->get(route(AdminNavigation::routeFor(AdminNavigation::PUBLICATIONS)))->assertOk();
    }
}
