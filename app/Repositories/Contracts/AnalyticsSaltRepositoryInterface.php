<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\AnalyticsSalt;
use Carbon\CarbonInterface;

/**
 * The daily salts of the analytics visitor hash (analytics_salts).
 */
interface AnalyticsSaltRepositoryInterface
{
    public function forDay(CarbonInterface $day): ?AnalyticsSalt;

    /**
     * Store the day's salt unless another request stored one first; returns the stored row.
     */
    public function firstOrCreate(CarbonInterface $day, string $salt): AnalyticsSalt;

    /**
     * Delete the salts of every day before the given one. Returns how many.
     */
    public function deleteBefore(CarbonInterface $day): int;

    public function count(): int;
}
