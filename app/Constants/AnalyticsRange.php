<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;
use Carbon\CarbonImmutable;

/**
 * The date ranges of the analytics report. Each preset ends today; Custom
 * takes its own first and last day. The comparison period is the same number
 * of days immediately before.
 */
enum AnalyticsRange: string
{
    use HasValues;

    case Today = 'today';
    case Week = '7d';
    case Month = '30d';
    case Year = '12m';
    case Custom = 'custom';

    public const self DEFAULT = self::Month;

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Today',
            self::Week => 'Last 7 days',
            self::Month => 'Last 30 days',
            self::Year => 'Last 12 months',
            self::Custom => 'Custom range',
        };
    }

    /**
     * First and last day (inclusive) of a preset ending on $today; a custom
     * range is passed through, swapped if given backwards.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function bounds(CarbonImmutable $today, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $today = $today->startOfDay();

        return match ($this) {
            self::Today => [$today, $today],
            self::Week => [$today->subDays(6), $today],
            self::Month => [$today->subDays(29), $today],
            self::Year => [$today->subMonthsNoOverflow(12)->addDay(), $today],
            self::Custom => self::ordered(($from ?? $today)->startOfDay(), ($to ?? $today)->startOfDay()),
        };
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function ordered(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $from->greaterThan($to) ? [$to, $from] : [$from, $to];
    }
}
