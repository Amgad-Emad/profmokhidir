<?php

namespace App\Jobs;

use App\Services\AnalyticsRollupService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * The nightly analytics job (scheduled in routes/console.php): roll up every
 * finished day of raw page views into the daily tables, then apply the
 * retention rules (raw events after 30 days, rollups after 25 months, old
 * visitor-hash salts at once).
 */
class RollUpAnalytics implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(AnalyticsRollupService $rollups): void
    {
        $days = $rollups->rollUpPending();
        $pruned = $rollups->prune();

        Log::info('Analytics rolled up.', ['days' => $days, 'pruned' => $pruned]);
    }
}
