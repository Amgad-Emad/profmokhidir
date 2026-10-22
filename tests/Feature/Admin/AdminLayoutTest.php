<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_sidebar_shows_the_lockup_group_headings_and_user(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Site Administrator']);

        $response = $this->actingAs($admin)->get(route(AdminNavigation::DASHBOARD_ROUTE))->assertOk();

        $response
            ->assertSee('id="admin-sidebar"', false)
            ->assertSee('data-variant="horizontal"', false)
            ->assertSee('Mohamed Osman Khidir')
            ->assertSee('Site Administrator')
            ->assertSee('Administrator')
            ->assertSee('Sign out')
            ->assertSee('livewire', false);

        foreach (AdminNavigation::groups() as $key => $label) {
            $response->assertSee('data-nav-group="'.$key.'"', false)->assertSee($label);
        }

        foreach (AdminNavigation::items() as $key => $item) {
            $response->assertSee('data-nav-item="'.$key.'"', false)->assertSee($item['label']);
        }
    }

    #[Test]
    public function editors_only_see_the_items_they_may_use(): void
    {
        $response = $this->actingAs(User::factory()->editor()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk();

        $response
            ->assertSee('data-nav-group="content"', false)
            ->assertSee('data-nav-group="tributes"', false)
            ->assertSee('data-nav-item="account"', false)
            ->assertDontSee('data-nav-group="community"', false)
            ->assertDontSee('data-nav-item="users"', false)
            ->assertDontSee('data-nav-item="applications"', false);
    }

    #[Test]
    public function the_active_item_is_marked_with_the_brass_rule(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('class="admin-nav-link admin-nav-link--active" aria-current="page" data-nav-item="dashboard" title="Dashboard">', false);

        $this->actingAs($admin)->get(route(AdminNavigation::routeFor(AdminNavigation::TRIBUTES)))
            ->assertOk()
            ->assertSee('admin-nav-link--active" aria-current="page" data-nav-item="tributes"', false)
            ->assertDontSee('admin-nav-link--active" aria-current="page" data-nav-item="dashboard"', false);
    }

    #[Test]
    public function the_sidebar_collapses_on_mobile_and_uses_only_brand_colours(): void
    {
        $content = $this->actingAs(User::factory()->admin()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aria-controls="admin-sidebar"', $content);
        $this->assertStringContainsString('x-bind:aria-expanded', $content);
        $this->assertStringContainsString('-translate-x-full', $content);
        $this->assertStringContainsString('lg:translate-x-0', $content);
        $this->assertStringContainsString('bg-surface-sunken', $content);

        foreach (['red', 'green', 'blue', 'yellow', 'emerald', 'rose', 'amber', 'gray', 'slate'] as $colour) {
            $this->assertDoesNotMatchRegularExpression('/\b(bg|text|border)-'.$colour.'-\d+/', $content, "Found a {$colour} utility");
        }
    }
}
