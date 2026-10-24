<?php

namespace Tests\Unit\Services;

use App\Constants\AdminNavigation;
use App\Constants\AuditAction;
use App\Constants\DashboardGreeting;
use App\Constants\StatusTone;
use App\Models\AuditLog;
use App\Models\Award;
use App\Models\ChatReport;
use App\Models\Commentary;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(DashboardService::class);
    }

    #[Test]
    public function published_counts_cover_every_public_section_and_ignore_drafts(): void
    {
        Publication::factory()->published()->count(3)->create();
        Publication::factory()->draft()->count(2)->create();
        Award::factory()->published()->create();
        Commentary::factory()->draft()->create();
        Tribute::factory()->approved()->count(2)->create();
        Tribute::factory()->pending()->create();

        $counts = $this->service->publishedCounts();

        $this->assertSame([
            AdminNavigation::PROFILE, AdminNavigation::CV, AdminNavigation::PUBLICATIONS, AdminNavigation::AWARDS,
            AdminNavigation::VIDEOS, AdminNavigation::ALBUMS, AdminNavigation::TEACHING, AdminNavigation::COMMENTARY,
            AdminNavigation::OTHER, AdminNavigation::TRIBUTES,
        ], array_keys($counts));

        $this->assertSame(3, $counts[AdminNavigation::PUBLICATIONS]['count']);
        $this->assertSame(1, $counts[AdminNavigation::AWARDS]['count']);
        $this->assertSame(0, $counts[AdminNavigation::COMMENTARY]['count']);
        $this->assertSame(2, $counts[AdminNavigation::TRIBUTES]['count']);
        $this->assertSame('Publications', $counts[AdminNavigation::PUBLICATIONS]['label']);
        $this->assertSame('admin.content.publications', $counts[AdminNavigation::PUBLICATIONS]['route']);
    }

    #[Test]
    public function queues_report_pending_tributes_applications_and_open_reports(): void
    {
        Tribute::factory()->pending()->count(2)->create();
        Tribute::factory()->approved()->create();
        User::factory()->applicant()->count(3)->create();
        User::factory()->rejectedApplicant()->create();
        ChatReport::factory()->create();
        ChatReport::factory()->resolved()->create();

        $queues = $this->service->queues();

        $this->assertSame(2, $queues[AdminNavigation::TRIBUTES]['count']);
        $this->assertSame(3, $queues[AdminNavigation::APPLICATIONS]['count']);
        $this->assertSame(1, $queues[AdminNavigation::CHAT_REPORTS]['count']);
        $this->assertSame(StatusTone::Bronze, $queues[AdminNavigation::TRIBUTES]['tone']);
        $this->assertSame('admin.community.reports', $queues[AdminNavigation::CHAT_REPORTS]['route']);
    }

    #[Test]
    public function empty_queues_are_cypress_and_do_not_need_attention(): void
    {
        $queues = $this->service->queues();

        foreach ($queues as $queue) {
            $this->assertSame(0, $queue['count']);
            $this->assertSame(StatusTone::Cypress, $queue['tone']);
        }

        $this->assertSame([], $this->service->needsAttention());
    }

    #[Test]
    public function needs_attention_is_limited_to_queues_the_user_may_act_on(): void
    {
        Tribute::factory()->pending()->create();
        User::factory()->applicant()->create();
        ChatReport::factory()->create();

        $this->assertSame(
            [AdminNavigation::TRIBUTES, AdminNavigation::APPLICATIONS, AdminNavigation::CHAT_REPORTS],
            array_keys($this->service->needsAttention()),
        );
        $this->assertSame(
            [AdminNavigation::TRIBUTES, AdminNavigation::APPLICATIONS, AdminNavigation::CHAT_REPORTS],
            array_keys($this->service->needsAttention(User::factory()->admin()->create())),
        );
        $this->assertSame(
            [AdminNavigation::TRIBUTES],
            array_keys($this->service->needsAttention(User::factory()->editor()->create())),
        );
    }

    #[Test]
    public function recent_activity_is_newest_first_and_limited(): void
    {
        AuditLog::factory()->count(12)->create();

        $activity = $this->service->recentActivity();

        $this->assertCount(DashboardService::RECENT_ACTIVITY_LIMIT, $activity);
        $this->assertTrue($activity->first()->id > $activity->last()->id);
        $this->assertTrue($activity->first()->relationLoaded('user'));
        $this->assertCount(3, $this->service->recentActivity(3));
    }

    #[Test]
    public function summary_bundles_everything_for_the_view(): void
    {
        Tribute::factory()->pending()->create();
        AuditLog::factory()->action(AuditAction::ADMIN_LOGIN)->create();

        $summary = $this->service->summary(User::factory()->editor()->create());

        $this->assertSame(['cards', 'actions', 'checklist', 'feed', 'greeting', 'twoFactorReminder', 'published', 'queues', 'attention', 'activity'], array_keys($summary));
        $this->assertContains($summary['greeting']['salutation'], DashboardGreeting::all());
        $this->assertCount(10, $summary['published']);
        $this->assertCount(3, $summary['queues']);
        $this->assertSame([AdminNavigation::TRIBUTES], array_keys($summary['attention']));
        $this->assertCount(1, $summary['activity']);
    }

    #[Test]
    public function the_greeting_follows_the_hour_and_sets_the_date_in_the_greeting_format(): void
    {
        $morning = $this->service->greeting(Carbon::parse('2026-10-22 08:15'));
        $evening = $this->service->greeting(Carbon::parse('2026-10-22 19:00'));

        $this->assertSame(['salutation' => DashboardGreeting::MORNING, 'date' => 'Thursday 22 October 2026', 'datetime' => '2026-10-22'], $morning);
        $this->assertSame(DashboardGreeting::EVENING, $evening['salutation']);
        $this->assertSame(DashboardGreeting::AFTERNOON, $this->service->greeting(Carbon::parse('2026-10-22 12:00'))['salutation']);
    }
}
