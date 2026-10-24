<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\Analytics;
use App\Services\AnalyticsService;
use Illuminate\Contracts\View\View;

/**
 * /privacy: what the site's own analytics records, in plain language. Served
 * only while analytics is on (404 otherwise, and the footer hides its link).
 */
class PrivacyController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function __invoke(): View
    {
        abort_unless($this->analytics->enabled(), 404);

        return view('privacy', [
            'idleSeconds' => Analytics::IDLE_SECONDS,
            'rawDays' => Analytics::RAW_RETENTION_DAYS,
            'rollupMonths' => Analytics::ROLLUP_RETENTION_MONTHS,
        ]);
    }
}
