<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\AuditAction;
use App\Models\AuditLog;
use App\Models\ChatReport;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardHomeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_home_shows_stat_cards_for_every_section_and_queue(): void
    {
        Publication::factory()->published()->count(4)->create();
        Tribute::factory()->pending()->count(2)->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertViewHasAll(['published', 'queues', 'attention', 'activity']);

        $response
            ->assertSee('data-stat="publications"', false)
            ->assertSee('data-stat="tributes"', false)
            ->assertSee('data-stat="applications"', false)
            ->assertSee('data-stat="chat-reports"', false)
            ->assertSee('Tributes pending review')
            ->assertSee('Community applications pending')
            ->assertSee('Chat reports open');

        $this->assertSame(4, $response->viewData('published')[AdminNavigation::PUBLICATIONS]['count']);
        $this->assertSame(2, $response->viewData('queues')[AdminNavigation::TRIBUTES]['count']);

        // The greeting: a salutation for the hour, the date in small caps, and "published" written as a word.
        $greeting = $response->viewData('greeting');
        $response
            ->assertSee('<h1 class="t-heading">'.$greeting['salutation'].', ', false)
            ->assertSee('<p class="smallcaps text-xl text-ash" data-greeting-date><time datetime="'.$greeting['datetime'].'">'.$greeting['date'].'</time></p>', false)
            ->assertSee('published</span>', false)
            ->assertDontSee('publisheds');
    }

    #[Test]
    public function needs_attention_links_to_each_queue_with_items(): void
    {
        Tribute::factory()->pending()->create();
        ChatReport::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('data-queue="tributes"', false)
            ->assertSee('data-queue="chat-reports"', false)
            ->assertDontSee('data-queue="applications"', false)
            ->assertSee(route(AdminNavigation::routeFor(AdminNavigation::TRIBUTES)))
            ->assertSee(route(AdminNavigation::routeFor(AdminNavigation::CHAT_REPORTS)))
            ->assertSee('1 tribute')
            ->assertSee('1 report');
    }

    #[Test]
    public function editors_see_only_the_queues_they_may_act_on(): void
    {
        Tribute::factory()->pending()->create();
        ChatReport::factory()->create();

        $this->actingAs(User::factory()->editor()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('data-queue="tributes"', false)
            ->assertDontSee('data-queue="chat-reports"', false)
            ->assertDontSee(route(AdminNavigation::routeFor(AdminNavigation::CHAT_REPORTS)));
    }

    #[Test]
    public function the_empty_states_are_designed(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('Nothing is waiting for review.')
            ->assertSee('No activity has been recorded yet.');
    }

    #[Test]
    public function recent_activity_lists_the_audit_log_newest_first(): void
    {
        $editor = User::factory()->editor()->create(['name' => 'Content Editor']);
        AuditLog::factory()->for($editor, 'user')->action(AuditAction::CONTENT_PUBLISHED)->create(['meta' => ['label' => 'Soil Fertility Handbook'], 'created_at' => now()->subHour()]);
        AuditLog::factory()->for($editor, 'user')->action(AuditAction::TRIBUTE_APPROVED)->create(['created_at' => now()]);

        $content = $this->actingAs(User::factory()->admin()->create())
            ->get(route(AdminNavigation::DASHBOARD_ROUTE))
            ->assertOk()
            ->assertSee('data-activity', false)
            ->assertSee('Content Editor')
            ->assertSee('published content')
            ->assertSee('Soil Fertility Handbook')
            ->assertSee('approved a tribute')
            ->getContent();

        $this->assertLessThan(strpos($content, 'published content'), strpos($content, 'approved a tribute'));
    }
}
