<?php

namespace Tests\Feature\Console;

use App\Console\Commands\UpdateGeoIpDatabase;
use App\Jobs\RollUpAnalytics;
use App\Models\AnalyticsDailyTotal;
use App\Models\AnalyticsEvent;
use App\Services\AnalyticsRollupService;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsScheduleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_rollup_runs_nightly_and_the_geoip_update_weekly(): void
    {
        $events = collect($this->app->make(Schedule::class)->events());

        $rollup = $events->first(fn ($event): bool => $event instanceof CallbackEvent && str_contains((string) $event->description, RollUpAnalytics::class));
        $this->assertNotNull($rollup);
        $this->assertSame('30 0 * * *', $rollup->expression);

        $geoip = $events->first(fn ($event): bool => str_contains((string) $event->command, 'analytics:update-geoip'));
        $this->assertNotNull($geoip);
        $this->assertSame('15 3 * * 3', $geoip->expression);
    }

    #[Test]
    public function the_job_rolls_up_finished_days_and_prunes_old_raw_events(): void
    {
        Carbon::setTestNow('2026-10-24 00:30:00');
        AnalyticsEvent::factory()->at(now()->subDay()->setTime(10, 0))->create();
        AnalyticsEvent::factory()->at(now()->subDays(40))->create();
        AnalyticsEvent::factory()->at(now()->setTime(0, 10))->create();

        (new RollUpAnalytics)->handle($this->app->make(AnalyticsRollupService::class));

        $this->assertTrue(AnalyticsDailyTotal::query()->whereDate('day', '2026-10-23')->exists());
        $this->assertFalse(AnalyticsDailyTotal::query()->whereDate('day', '2026-10-24')->exists());
        $this->assertSame(2, AnalyticsEvent::query()->count());

        Carbon::setTestNow();
    }

    #[Test]
    public function the_geoip_command_does_nothing_without_a_licence_key(): void
    {
        config(['services.maxmind.license_key' => null]);
        Http::fake();

        $this->artisan(UpdateGeoIpDatabase::class)->expectsOutputToContain('MAXMIND_LICENSE_KEY is not set')->assertSuccessful();
        Http::assertNothingSent();
    }
}
