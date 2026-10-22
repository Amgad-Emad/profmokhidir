<?php

namespace Tests\Unit\Services;

use App\Constants\AdminNavigation;
use App\Models\ChatReport;
use App\Models\Tribute;
use App\Models\User;
use App\Services\AdminShellService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminShellServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminShellService $shell;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shell = $this->app->make(AdminShellService::class);
    }

    #[Test]
    public function the_sidebar_marks_the_active_item_and_counts_what_the_user_may_act_on(): void
    {
        Tribute::factory()->pending()->count(2)->create();
        ChatReport::factory()->create();

        $editor = $this->shell->sidebar(User::factory()->editor()->create(), 'admin.tributes.index');
        $items = collect($editor['groups'])->flatMap(fn (array $group) => $group['items'])->keyBy('key');

        $this->assertTrue($items[AdminNavigation::TRIBUTES]['active']);
        $this->assertSame(2, $items[AdminNavigation::TRIBUTES]['count']);
        $this->assertFalse($items->has(AdminNavigation::CHAT_REPORTS));
        $this->assertFalse($editor['dashboard']['active']);
        $this->assertSame('heart', $items[AdminNavigation::TRIBUTES]['icon']);

        $admin = $this->shell->counts(User::factory()->admin()->create());
        $this->assertSame([AdminNavigation::TRIBUTES => 2, AdminNavigation::CHAT_REPORTS => 1], $admin);
    }

    #[Test]
    public function nested_routes_keep_their_section_active(): void
    {
        $sidebar = $this->shell->sidebar(User::factory()->editor()->create(), 'admin.content.publications.edit');
        $items = collect($sidebar['groups'])->flatMap(fn (array $group) => $group['items'])->keyBy('key');

        $this->assertTrue($items[AdminNavigation::PUBLICATIONS]['active']);
    }

    #[Test]
    public function breadcrumbs_run_from_the_dashboard_through_the_group_and_screen_to_the_record(): void
    {
        $this->assertSame([['label' => 'Dashboard', 'url' => null]], $this->shell->breadcrumbs(AdminNavigation::DASHBOARD_ROUTE));

        $this->assertSame([
            ['label' => 'Dashboard', 'url' => route(AdminNavigation::DASHBOARD_ROUTE)],
            ['label' => 'Content', 'url' => null],
            ['label' => 'Publications', 'url' => null],
        ], $this->shell->breadcrumbs('admin.content.publications', 'Publications'));

        $this->assertSame([
            ['label' => 'Dashboard', 'url' => route(AdminNavigation::DASHBOARD_ROUTE)],
            ['label' => 'Content', 'url' => null],
            ['label' => 'Publications', 'url' => route('admin.content.publications')],
            ['label' => 'Soil and Water', 'url' => null],
        ], $this->shell->breadcrumbs('admin.content.publications.edit', 'Soil and Water'));
    }

    #[Test]
    public function the_palette_offers_new_actions_and_screens_the_user_may_open(): void
    {
        $editor = collect($this->shell->paletteEntries(User::factory()->editor()->create()));

        $this->assertTrue($editor->contains('label', 'New publication'));
        $this->assertTrue($editor->contains('label', 'Upload photos'));
        $this->assertTrue($editor->contains('label', 'Review tributes'));
        $this->assertFalse($editor->contains('label', 'Users & roles'));

        $admin = collect($this->shell->paletteEntries(User::factory()->admin()->create()));
        $this->assertTrue($admin->contains('label', 'Users & roles'));
    }
}
