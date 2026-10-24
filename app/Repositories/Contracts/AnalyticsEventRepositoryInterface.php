<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\AnalyticsEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Raw analytics page views (analytics_events).
 */
interface AnalyticsEventRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): AnalyticsEvent;

    /**
     * Insert many page views at once (the local demo data), in chunks.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function insertMany(array $rows): int;

    public function findByView(string $viewId): ?AnalyticsEvent;

    /**
     * Raise a view's engaged seconds and scroll depth to the reported values
     * (beacons report totals so far, so values never go down). Only the view's
     * own visitor (same hash) can update it. Returns whether a row matched.
     */
    public function recordEngagement(string $viewId, string $visitorHash, int $engagedSeconds, int $scrollDepth): bool;

    /**
     * Every page view of one day, oldest first, with only the columns the rollup reads.
     *
     * @return Collection<int, AnalyticsEvent>
     */
    public function forDay(CarbonInterface $day): Collection;

    /**
     * Days (Y-m-d) that have page views, before the given day, oldest first.
     *
     * @return list<string>
     */
    public function daysBefore(CarbonInterface $day): array;

    /**
     * Delete page views that happened before the given moment. Returns how many.
     */
    public function deleteBefore(CarbonInterface $moment): int;

    public function count(): int;
}
