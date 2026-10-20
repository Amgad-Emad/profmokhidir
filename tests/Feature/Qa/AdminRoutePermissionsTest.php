<?php

namespace Tests\Feature\Qa;

use App\Constants\AdminNavigation;
use App\Constants\ContentTypes;
use App\Constants\Disks;
use App\Constants\Roles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Editor versus admin on every GET route under /admin, derived from the router
 * so a new screen cannot be added without a permission.
 */
class AdminRoutePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Disks::PUBLIC);
        Storage::fake(Disks::PRIVATE);
    }

    /**
     * Every admin GET route with a URL it can be requested at (edit routes get a fresh record).
     *
     * @return array<string, array{route: RouteDefinition, url: string}>
     */
    private function adminRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null || ! str_starts_with($name, AdminNavigation::ROUTE_PREFIX) || ! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $parameters = [];

            foreach ($route->parameterNames() as $parameter) {
                $parameters[$parameter] = $this->recordFor($parameter)->getKey();
            }

            $routes[$name] = ['route' => $route, 'url' => route($name, $parameters)];
        }

        ksort($routes);

        return $routes;
    }

    private function recordFor(string $parameter): object
    {
        foreach (array_keys(ContentTypes::all()) as $type) {
            if (ContentTypes::routeParameter($type) === $parameter) {
                return ContentTypes::get($type)['model']::factory()->create();
            }
        }

        $this->fail("No content type owns the route parameter {$parameter}");
    }

    private function permissionOf(RouteDefinition $route): ?string
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (str_starts_with($middleware, 'permission:')) {
                return substr($middleware, strlen('permission:'));
            }
        }

        return null;
    }

    #[Test]
    public function there_is_an_admin_route_for_every_navigation_item_and_content_screen(): void
    {
        $names = array_keys($this->adminRoutes());

        $this->assertContains(AdminNavigation::DASHBOARD_ROUTE, $names);

        foreach (AdminNavigation::items() as $key => $item) {
            if ($key !== AdminNavigation::ACCOUNT) {
                $this->assertContains($item['route'], $names, "Navigation item {$key} has no route");
            }
        }

        foreach (array_keys(ContentTypes::all()) as $type) {
            $this->assertContains(ContentTypes::indexRoute($type), $names);
            $this->assertContains(ContentTypes::editRoute($type), $names);

            if ($type !== ContentTypes::PROFILE) {
                $this->assertContains(ContentTypes::createRoute($type), $names);
            }
        }

        $this->assertGreaterThanOrEqual(30, count($names));
    }

    #[Test]
    public function every_admin_route_is_guarded_by_auth_verification_staff_role_two_factor_and_a_permission(): void
    {
        foreach ($this->adminRoutes() as $name => ['route' => $route]) {
            $middleware = $route->gatherMiddleware();

            foreach (['auth', 'verified', 'role:'.implode('|', Roles::staff()), 'two-factor'] as $required) {
                $this->assertContains($required, $middleware, "{$name} is missing {$required}");
            }

            if ($name !== AdminNavigation::DASHBOARD_ROUTE) {
                $this->assertNotNull($this->permissionOf($route), "{$name} has no permission middleware");
            }
        }
    }

    #[Test]
    public function guests_are_redirected_and_members_applicants_and_plain_users_are_forbidden_everywhere(): void
    {
        $outsiders = [
            'member' => User::factory()->member()->create(),
            'applicant' => User::factory()->applicant()->create(),
            'rejected' => User::factory()->rejectedApplicant()->create(),
            'no role' => User::factory()->create(),
        ];

        $routes = $this->adminRoutes();

        // Guests first: actingAs() keeps the test client signed in for the rest of the test.
        foreach ($routes as $name => ['url' => $url]) {
            $this->get($url)->assertRedirect(route('login'));
        }

        foreach ($outsiders as $label => $user) {
            foreach ($routes as $name => ['url' => $url]) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }
        }
    }

    #[Test]
    public function admins_can_open_every_admin_route(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ($this->adminRoutes() as $name => ['url' => $url]) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    #[Test]
    public function editors_open_exactly_the_routes_their_permissions_allow(): void
    {
        $editor = User::factory()->editor()->create();
        $allowed = [];
        $forbidden = [];

        foreach ($this->adminRoutes() as $name => ['route' => $route, 'url' => $url]) {
            $permission = $this->permissionOf($route);
            $response = $this->actingAs($editor)->get($url);

            if ($permission === null || $editor->can($permission)) {
                $response->assertOk();
                $allowed[] = $name;
            } else {
                $response->assertForbidden();
                $forbidden[] = $name;
            }
        }

        foreach ([AdminNavigation::APPLICATIONS, AdminNavigation::MEMBERS, AdminNavigation::CHAT_REPORTS, AdminNavigation::USERS] as $adminOnly) {
            $this->assertContains(AdminNavigation::routeFor($adminOnly), $forbidden);
        }

        foreach ([AdminNavigation::TRIBUTES, AdminNavigation::PUBLICATIONS, AdminNavigation::MEDIA_LIBRARY, AdminNavigation::PROFILE] as $editable) {
            $this->assertContains(AdminNavigation::routeFor($editable), $allowed);
        }

        $this->assertContains(AdminNavigation::DASHBOARD_ROUTE, $allowed);
    }

    #[Test]
    public function admins_without_confirmed_two_factor_are_sent_to_set_it_up(): void
    {
        $admin = User::factory()->admin()->withoutTwoFactor()->create();

        $this->actingAs($admin)->get(route(AdminNavigation::DASHBOARD_ROUTE))->assertRedirect();
        $this->assertStringContainsString(route(AdminNavigation::ACCOUNT_ROUTE), $this->actingAs($admin)->get(route(AdminNavigation::DASHBOARD_ROUTE))->headers->get('Location'));
    }
}
