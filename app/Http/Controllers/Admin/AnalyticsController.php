<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Constants\Analytics;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnalyticsReportRequest;
use App\Models\AnalyticsDailyTotal;
use App\Services\AnalyticsReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * /admin/analytics: the audience report for a date range, and a CSV of each of its tables.
 */
class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsReportService $reports) {}

    public function index(AnalyticsReportRequest $request): View
    {
        Gate::authorize('viewAny', AnalyticsDailyTotal::class);

        $period = $this->reports->period($request->range(), $request->validated('from'), $request->validated('to'));

        return view('admin.analytics.index', [
            ...$this->reports->report($period, $request->section()),
            'query' => array_filter($request->validated(), fn (mixed $value): bool => $value !== null && $value !== ''),
        ]);
    }

    public function export(AnalyticsReportRequest $request, string $export): StreamedResponse
    {
        Gate::authorize('export', AnalyticsDailyTotal::class);

        $period = $this->reports->period($request->range(), $request->validated('from'), $request->validated('to'));
        $rows = $this->reports->csv($export, $period, $request->section());
        $filename = sprintf('analytics-%s-%s-to-%s.csv', $export, $period['from']->toDateString(), $period['to']->toDateString());

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');

            foreach ($rows as $row) {
                // A leading =, +, - or @ would run as a formula in a spreadsheet.
                fputcsv($out, array_map(fn (mixed $cell): mixed => is_string($cell) && preg_match('/^[=+\-@]/', $cell) === 1 ? "'".$cell : $cell, $row), escape: '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}
