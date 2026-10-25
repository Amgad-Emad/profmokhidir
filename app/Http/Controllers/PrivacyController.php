<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\Analytics;
use App\Services\AnalyticsService;
use App\Services\PreviewService;
use Illuminate\Contracts\View\View;

/**
 * /privacy: what the site's own analytics records, in plain language. Served
 * only while analytics is on (404 otherwise, and the footer hides its link).
 */
class PrivacyController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly PreviewService $preview,
    ) {}

    public function __invoke(): View
    {
        // A signed preview from Admin › Pages may show it before analytics is switched on.
        abort_unless($this->analytics->enabled() || $this->preview->active(), 404);

        return view('privacy', [
            'idleSeconds' => Analytics::IDLE_SECONDS,
            'rawDays' => Analytics::RAW_RETENTION_DAYS,
            'rollupMonths' => Analytics::ROLLUP_RETENTION_MONTHS,
        ]);
    }
}
