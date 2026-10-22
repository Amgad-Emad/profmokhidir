<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The frame around every admin screen: top bar, breadcrumbs, command palette,
 * sidebar counts and collapse, toasts, shortcuts sheet and leave guard.
 */
class AdminShellTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_top_bar_carries_breadcrumbs_search_theme_view_site_and_the_user_menu(): void
    {
        $publication = Publication::factory()->create(['title' => 'Soil and Water']);

        $this->actingAs(User::factory()->editor()->create(['name' => 'Amina Hassan']))
            ->get(route('admin.content.publications.edit', ['publication' => $publication->getKey()]))
            ->assertOk()
            ->assertSee('data-admin-breadcrumbs', false)
            ->assertSeeInOrder(['Dashboard', 'Content', 'Publications', '<span aria-current="page">Soil and Water</span>'], false)
            ->assertSee('data-palette-open', false)
            ->assertSee('id="theme-switch-admin"', false)
            ->assertSee('data-view-site', false)
            ->assertSee('data-user-menu', false)
            ->assertSee('Keyboard shortcuts')
            ->assertSee('Sign out')
            ->assertSee('<span class="admin-user-menu__initials" aria-hidden="true">AH</span>', false);
    }

    #[Test]
    public function the_frame_holds_the_palette_toasts_shortcuts_and_leave_guard_once(): void
    {
        $content = $this->actingAs(User::factory()->editor()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('data-command-palette', false)
            ->assertSee('u0022endpoint', false)
            ->assertSee('New publication')
            ->assertSee('data-toasts', false)
            ->assertSee('role="status" aria-live="polite"', false)
            ->assertSee('role="alert" aria-live="assertive"', false)
            ->assertSee('data-shortcuts-sheet', false)
            ->assertSee('data-leave-guard', false)
            ->assertSee('Leave without saving?')
            ->getContent();

        $this->assertSame(1, substr_count($content, 'data-command-palette'));
    }

    #[Test]
    public function sidebar_groups_collapse_and_queues_show_counts(): void
    {
        Tribute::factory()->pending()->count(3)->create();

        $this->actingAs(User::factory()->editor()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('aria-controls="nav-group-content"', false)
            ->assertSee('data-nav-count="tributes">3<span class="sr-only"> waiting</span>', false)
            ->assertSee('Collapse to icons')
            ->assertSee('x-data="adminShell"', false);
    }

    #[Test]
    public function a_message_flashed_before_a_redirect_opens_as_a_toast(): void
    {
        $this->actingAs(User::factory()->editor()->create())
            ->withSession(['admin.status' => 'Publication created.'])
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('Publication created.');
    }
}
