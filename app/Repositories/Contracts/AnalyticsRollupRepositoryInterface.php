<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\AnalyticsDimension;
use Carbon\CarbonInterface;

/**
 * The nightly analytics rollups (analytics_daily_totals, _pages, _countries,
 * _referrers and _technologies). Reads sum a range of days; a range's figures
 * for today are added by the report service from the raw events.
 *
 * A day's rollup has this shape (AnalyticsRollupService::aggregate()):
 * totals: {visitors, returning_visitors, visits, pageviews, engaged_seconds},
 * pages: list of {path, title, section, views, visitors, engaged_seconds, scroll_depth_total},
 * countries: list of {country, visitors, pageviews},
 * referrers: list of {referrer_domain, visitors, pageviews},
 * technologies: list of {dimension, value, visitors, pageviews}.
 */
interface AnalyticsRollupRepositoryInterface
{
    /**
     * Replace everything stored for one day with the given rollup.
     *
     * @param  array{totals: array<string, int>, pages: list<array<string, mixed>>, countries: list<array<string, mixed>>, referrers: list<array<string, mixed>>, technologies: list<array<string, mixed>>}  $rollup
     */
    public function replaceDay(CarbonInterface $day, array $rollup): void;

    public function hasDay(CarbonInterface $day): bool;

    /**
     * Site-wide figures per day, keyed by Y-m-d, oldest first.
     *
     * @return array<string, array{visitors: int, returning_visitors: int, visits: int, pageviews: int, engaged_seconds: int}>
     */
    public function dailyTotals(CarbonInterface $from, CarbonInterface $to): array;

    /**
     * Pages summed over the range, optionally within one section.
     *
     * @return list<array{path: string, title: string|null, section: string, views: int, visitors: int, engaged_seconds: int, scroll_depth_total: int}>
     */
    public function pages(CarbonInterface $from, CarbonInterface $to, ?string $section = null): array;

    /**
     * @return list<array{country: string, visitors: int, pageviews: int}>
     */
    public function countries(CarbonInterface $from, CarbonInterface $to): array;

    /**
     * @return list<array{referrer_domain: string, visitors: int, pageviews: int}>
     */
    public function referrers(CarbonInterface $from, CarbonInterface $to): array;

    /**
     * @return list<array{value: string, visitors: int, pageviews: int}>
     */
    public function technologies(AnalyticsDimension $dimension, CarbonInterface $from, CarbonInterface $to): array;

    /**
     * Delete every rollup row of the days before the given one. Returns how many rows.
     */
    public function deleteBefore(CarbonInterface $day): int;

    public function hasAny(): bool;
}
