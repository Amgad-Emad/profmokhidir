<?php

namespace Database\Seeders;

use App\Constants\Environments;
use App\Repositories\Contracts\AnalyticsRollupRepositoryInterface;
use App\Services\AnalyticsRollupService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Local only: 90 days of realistic analytics so the dashboard's Audience
 * section and /admin/analytics can be reviewed before the client approves
 * real collection. Generates raw page views (AnalyticsEventSeeder), rolls up
 * every finished day with the real rollup code (so the five daily tables
 * agree with each other), then applies retention, which leaves only the last
 * 30 days of raw events. Never runs outside the local environment, and does
 * nothing when rollups already exist.
 */
class AnalyticsDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(AnalyticsRollupService $rollups, AnalyticsRollupRepositoryInterface $stored): void
    {
        if (! app()->environment(Environments::developer())) {
            $this->command?->warn('AnalyticsDemoSeeder only runs in the local environment.');

            return;
        }

        if ($stored->hasAny()) {
            $this->command?->info('Analytics rollups already exist; demo analytics not seeded again.');

            return;
        }

        $this->call([AnalyticsSaltSeeder::class, AnalyticsEventSeeder::class]);

        $days = $rollups->rollUpPending(Carbon::now());
        $rollups->prune();

        $this->command?->info(sprintf('Rolled up %d days of demo analytics.', count($days)));
    }
}
