<?php

namespace Tests\Feature\Database;

use App\Models\AnalyticsDailyCountry;
use App\Models\AnalyticsDailyPage;
use App\Models\AnalyticsDailyReferrer;
use App\Models\AnalyticsDailyTechnology;
use App\Models\AnalyticsDailyTotal;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSalt;
use Database\Seeders\AnalyticsDemoSeeder;
use Database\Seeders\AnalyticsEventSeeder;
use Database\Seeders\AnalyticsSaltSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsSeedersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_demo_seeders_never_run_outside_the_local_environment(): void
    {
        foreach (['testing', 'production', 'staging'] as $environment) {
            $this->app['env'] = $environment;

            // Called directly: db:seed itself asks for confirmation in production.
            (new AnalyticsDemoSeeder)->setContainer($this->app)->__invoke();
            (new AnalyticsEventSeeder)->setContainer($this->app)->__invoke();
        }

        $this->app['env'] = 'testing';

        $this->assertSame(0, AnalyticsEvent::query()->count());
        $this->assertSame(0, AnalyticsDailyTotal::query()->count());
    }

    #[Test]
    public function locally_the_demo_seeder_builds_90_days_of_consistent_rollups_and_keeps_30_days_of_raw_events(): void
    {
        $this->app['env'] = 'local';

        $this->seed(AnalyticsDemoSeeder::class);

        $this->assertSame(90, AnalyticsDailyTotal::query()->count());
        $this->assertGreaterThan(0, AnalyticsDailyPage::query()->count());
        $this->assertGreaterThan(0, AnalyticsDailyCountry::query()->count());
        $this->assertGreaterThan(0, AnalyticsDailyReferrer::query()->count());
        $this->assertGreaterThan(0, AnalyticsDailyTechnology::query()->count());
        $this->assertFalse(AnalyticsEvent::query()->where('occurred_at', '<', now()->subDays(30))->exists());
        $this->assertTrue(AnalyticsEvent::query()->exists());
        $this->assertSame(1, AnalyticsSalt::query()->count());

        // The per-day tables agree with the totals.
        $day = AnalyticsDailyTotal::query()->orderByDesc('visitors')->first();
        $this->assertSame($day->pageviews, (int) AnalyticsDailyPage::query()->whereDate('day', $day->day)->sum('views'));
        $this->assertSame($day->visitors, (int) AnalyticsDailyCountry::query()->whereDate('day', $day->day)->sum('visitors'));
        $this->assertGreaterThan(20, $day->visitors);

        // Running again adds nothing.
        $this->seed(AnalyticsDemoSeeder::class);
        $this->assertSame(90, AnalyticsDailyTotal::query()->count());

        $this->app['env'] = 'testing';
    }

    #[Test]
    public function the_salt_seeder_leaves_only_todays_salt_and_the_demo_seeder_is_registered(): void
    {
        AnalyticsSalt::factory()->yesterday()->create();

        $this->seed(AnalyticsSaltSeeder::class);

        $this->assertSame([now()->toDateString()], AnalyticsSalt::query()->get()->map(fn (AnalyticsSalt $salt): string => $salt->day->toDateString())->all());
        $this->assertStringContainsString('AnalyticsDemoSeeder::class', (string) file_get_contents((new \ReflectionClass(DatabaseSeeder::class))->getFileName()));
    }
}
