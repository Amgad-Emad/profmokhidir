<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Analytics;
use App\Constants\AnalyticsDimension;
use App\Constants\AnalyticsRange;
use App\Constants\DateFormats;
use App\Constants\DeviceType;
use App\Repositories\Contracts\AnalyticsEventRepositoryInterface;
use App\Repositories\Contracts\AnalyticsRollupRepositoryInterface;
use App\Support\Countries;
use App\Support\Duration;
use App\Support\WorldMap;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * The figures of the analytics report (/admin/analytics), the dashboard's
 * Audience section and the CSV exports. Finished days are read from the
 * rollups; today (and any day the nightly job has not rolled up yet) is
 * computed from the raw events, so the report is always current. Visitors are
 * counted once per day (the visitor hash changes daily), so a range's
 * visitors are the sum of its daily visitors.
 */
class AnalyticsReportService
{
    /** @var array<string, array<string, mixed>> Live aggregates of raw days, computed once per request. */
    private array $live = [];

    public function __construct(
        private readonly AnalyticsRollupRepositoryInterface $rollups,
        private readonly AnalyticsEventRepositoryInterface $events,
        private readonly AnalyticsRollupService $rollupService,
        private readonly AnalyticsService $analytics,
    ) {}

    /**
     * The reporting period and the comparison period of equal length right before it.
     *
     * @return array{range: AnalyticsRange, from: CarbonImmutable, to: CarbonImmutable, days: int, previous_from: CarbonImmutable, previous_to: CarbonImmutable, label: string, comparison: string}
     */
    public function period(AnalyticsRange $range, ?string $from = null, ?string $to = null, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::parse(Carbon::now()->toDateString());
        [$start, $end] = $range->bounds(
            $today,
            $from !== null && $from !== '' ? CarbonImmutable::parse($from) : null,
            $to !== null && $to !== '' ? CarbonImmutable::parse($to) : null,
        );
        $days = (int) $start->diffInDays($end) + 1;

        return [
            'range' => $range,
            'from' => $start,
            'to' => $end,
            'days' => $days,
            'previous_from' => $start->subDays($days),
            'previous_to' => $start->subDay(),
            'label' => $days === 1 ? $start->format(DateFormats::LONG) : $start->format(DateFormats::LONG).' – '.$end->format(DateFormats::LONG),
            'comparison' => match ($range) {
                AnalyticsRange::Today => 'yesterday',
                AnalyticsRange::Year => 'the previous 12 months',
                default => $days === 1 ? 'the day before' : 'the previous '.$days.' days',
            },
        ];
    }

    /**
     * Everything /admin/analytics shows for a period, optionally with the pages limited to one section.
     *
     * @param  array{range: AnalyticsRange, from: CarbonImmutable, to: CarbonImmutable, days: int, previous_from: CarbonImmutable, previous_to: CarbonImmutable, label: string, comparison: string}  $period
     * @return array<string, mixed>
     */
    public function report(array $period, ?string $section = null): array
    {
        $current = $this->gather($period['from'], $period['to']);
        $previous = $this->gather($period['previous_from'], $period['previous_to']);
        $countries = $this->ranked($current['countries'], 'country', fn (string $code): string => Countries::name($code), fn (string $code): string => Countries::flag($code));
        $maxCountry = max([0, ...array_map(fn (array $row): int => $row['visitors'], array_filter($countries, fn (array $row): bool => Countries::isKnown($row['key'])))]);

        return [
            'enabled' => $this->analytics->enabled(),
            'period' => $period,
            'headline' => $this->headline($current['daily'], $previous['daily']),
            'series' => $this->series($current['daily'], $previous['daily'], $period['days']),
            'countries' => array_slice($countries, 0, Analytics::LIST_LIMIT),
            'countryCount' => count(array_filter($countries, fn (array $row): bool => Countries::isKnown($row['key']))),
            'map' => array_map(fn (array $row): array => [...$row, 'tier' => WorldMap::tier($row['visitors'], $maxCountry)], array_values(array_filter($countries, fn (array $row): bool => Countries::isKnown($row['key'])))),
            'pages' => array_slice($this->pages($current['pages'], $section), 0, Analytics::PAGES_LIMIT),
            'section' => $section,
            'sections' => Analytics::sections(),
            'referrers' => array_slice($this->referrers($current['referrers']), 0, Analytics::LIST_LIMIT),
            'devices' => $this->ranked($current['technologies'][AnalyticsDimension::Device->value], 'value', fn (string $value): string => DeviceType::tryFrom($value)?->label() ?? $value),
            'browsers' => array_slice($this->ranked($current['technologies'][AnalyticsDimension::Browser->value], 'value', fn (string $value): string => $value), 0, Analytics::LIST_LIMIT),
            'campaigns' => array_slice($this->ranked($current['technologies'][AnalyticsDimension::UtmSource->value], 'value', fn (string $value): string => $value), 0, Analytics::LIST_LIMIT),
            'hasData' => array_sum(array_column($current['daily'], 'pageviews')) > 0,
        ];
    }

    /**
     * The dashboard's Audience section: the last 30 days against the 30 before, the top pages and countries.
     *
     * @return array<string, mixed>
     */
    public function audience(?CarbonImmutable $today = null): array
    {
        $period = $this->period(AnalyticsRange::DEFAULT, today: $today);
        $current = $this->gather($period['from'], $period['to']);
        $previous = $this->gather($period['previous_from'], $period['previous_to']);

        return [
            'enabled' => $this->analytics->enabled(),
            'period' => $period,
            'headline' => $this->headline($current['daily'], $previous['daily']),
            'pages' => array_slice($this->pages($current['pages']), 0, Analytics::AUDIENCE_LIMIT),
            'countries' => array_slice($this->ranked($current['countries'], 'country', fn (string $code): string => Countries::name($code), fn (string $code): string => Countries::flag($code)), 0, Analytics::AUDIENCE_LIMIT),
            'hasData' => array_sum(array_column($current['daily'], 'pageviews')) > 0,
        ];
    }

    /**
     * One table of the report as CSV rows (a header row first). Every row, not only the top ones.
     *
     * @param  array{range: AnalyticsRange, from: CarbonImmutable, to: CarbonImmutable, days: int, previous_from: CarbonImmutable, previous_to: CarbonImmutable, label: string, comparison: string}  $period
     * @return list<list<string|int|float>>
     */
    public function csv(string $export, array $period, ?string $section = null): array
    {
        $data = $this->gather($period['from'], $period['to']);
        $share = fn (array $row): string => number_format($row['share'], 1, '.', '');

        return match ($export) {
            Analytics::EXPORT_VISITORS => [
                ['Date', 'Unique visitors', 'Returning visitors', 'Visits', 'Page views', 'Engaged seconds'],
                ...array_map(fn (string $day, array $row): array => [$day, $row['visitors'], $row['returning_visitors'], $row['visits'], $row['pageviews'], $row['engaged_seconds']], array_keys($data['daily']), $data['daily']),
            ],
            Analytics::EXPORT_COUNTRIES => [
                ['Country code', 'Country', 'Visitors', 'Share (%)', 'Page views'],
                ...array_map(fn (array $row): array => [$row['key'], $row['label'], $row['visitors'], $share($row), $row['pageviews']], $this->ranked($data['countries'], 'country', fn (string $code): string => Countries::name($code))),
            ],
            Analytics::EXPORT_PAGES => [
                ['Page', 'Path', 'Section', 'Total engaged seconds', 'Average engaged seconds per view', 'Views', 'Average scroll depth (%)'],
                ...array_map(fn (array $row): array => [$row['label'], $row['path'], $row['section_label'], $row['engaged_seconds'], round($row['average_engaged'], 1), $row['views'], round($row['average_scroll'], 1)], $this->pages($data['pages'], $section)),
            ],
            Analytics::EXPORT_REFERRERS => [
                ['Referrer', 'Visitors', 'Share (%)', 'Page views'],
                ...array_map(fn (array $row): array => [$row['label'], $row['visitors'], $share($row), $row['pageviews']], $this->referrers($data['referrers'])),
            ],
            Analytics::EXPORT_DEVICES, Analytics::EXPORT_BROWSERS => [
                [$export === Analytics::EXPORT_DEVICES ? 'Device' : 'Browser', 'Visitors', 'Share (%)', 'Page views'],
                ...array_map(fn (array $row): array => [$row['label'], $row['visitors'], $share($row), $row['pageviews']], $this->ranked(
                    $data['technologies'][$export === Analytics::EXPORT_DEVICES ? AnalyticsDimension::Device->value : AnalyticsDimension::Browser->value],
                    'value',
                    fn (string $value): string => DeviceType::tryFrom($value)?->label() ?? $value,
                )),
            ],
            default => throw new \InvalidArgumentException(sprintf('Unknown analytics export "%s".', $export)),
        };
    }

    /**
     * The four headline figures with their change against the comparison period.
     *
     * @param  array<string, array{visitors: int, returning_visitors: int, visits: int, pageviews: int, engaged_seconds: int}>  $current
     * @param  array<string, array{visitors: int, returning_visitors: int, visits: int, pageviews: int, engaged_seconds: int}>  $previous
     * @return array<string, array{label: string, value: float|int, display: string, previous: float|int, change: float|null, hint: string}>
     */
    public function headline(array $current, array $previous): array
    {
        $figures = function (array $daily): array {
            $visitors = array_sum(array_column($daily, 'visitors'));
            $visits = array_sum(array_column($daily, 'visits'));

            return [
                'visitors' => $visitors,
                'pageviews' => array_sum(array_column($daily, 'pageviews')),
                'engaged' => $visits > 0 ? array_sum(array_column($daily, 'engaged_seconds')) / $visits : 0,
                'returning' => $visitors > 0 ? array_sum(array_column($daily, 'returning_visitors')) / $visitors * 100 : 0,
            ];
        };

        $now = $figures($current);
        $before = $figures($previous);

        $card = fn (string $key, string $label, string $display, string $hint): array => [
            'label' => $label,
            'value' => $now[$key],
            'display' => $display,
            'previous' => $before[$key],
            'change' => $before[$key] > 0 ? ($now[$key] - $before[$key]) / $before[$key] * 100 : null,
            'hint' => $hint,
        ];

        return [
            'visitors' => $card('visitors', 'Unique visitors', number_format($now['visitors']), 'Each visitor is counted once per day.'),
            'pageviews' => $card('pageviews', 'Page views', number_format($now['pageviews']), 'Every page opened.'),
            'engaged' => $card('engaged', 'Engaged time per visit', Duration::short($now['engaged']), 'Time spent reading, on average.'),
            'returning' => $card('returning', 'Returning visitors', number_format($now['returning'], 0).'%', 'Came back for another visit the same day.'),
        ];
    }

    /**
     * Raw rollup data for a range: every day's totals (zeros for empty days) and the summed lists,
     * with today and any day not rolled up yet computed from the raw events.
     *
     * @return array{daily: array<string, array{visitors: int, returning_visitors: int, visits: int, pageviews: int, engaged_seconds: int}>, pages: list<array<string, mixed>>, countries: list<array<string, mixed>>, referrers: list<array<string, mixed>>, technologies: array<string, list<array<string, mixed>>>}
     */
    public function gather(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $stored = $this->rollups->dailyTotals($from, $to);
        $today = Carbon::now()->toDateString();
        $daily = [];

        for ($day = $from; $day->lessThanOrEqualTo($to); $day = $day->addDay()) {
            $daily[$day->toDateString()] = $stored[$day->toDateString()] ?? ['visitors' => 0, 'returning_visitors' => 0, 'visits' => 0, 'pageviews' => 0, 'engaged_seconds' => 0];
        }

        $data = [
            'pages' => $this->rollups->pages($from, $to),
            'countries' => $this->rollups->countries($from, $to),
            'referrers' => $this->rollups->referrers($from, $to),
            'technologies' => [],
        ];

        foreach (AnalyticsDimension::cases() as $dimension) {
            $data['technologies'][$dimension->value] = $this->rollups->technologies($dimension, $from, $to);
        }

        foreach ($this->events->daysBefore($to->addDay()) as $day) {
            if ($day < $from->toDateString() || ($day !== $today && isset($stored[$day]))) {
                continue;
            }

            $live = $this->live[$day] ??= $this->rollupService->aggregate(CarbonImmutable::parse($day));
            $daily[$day] = $live['totals'];
            $data['pages'] = self::merge($data['pages'], $live['pages'], ['path'], ['views', 'visitors', 'engaged_seconds', 'scroll_depth_total']);
            $data['countries'] = self::merge($data['countries'], $live['countries'], ['country'], ['visitors', 'pageviews']);
            $data['referrers'] = self::merge($data['referrers'], $live['referrers'], ['referrer_domain'], ['visitors', 'pageviews']);

            foreach (AnalyticsDimension::cases() as $dimension) {
                $rows = array_values(array_filter($live['technologies'], fn (array $row): bool => $row['dimension'] === $dimension->value));
                $rows = array_map(fn (array $row): array => ['value' => $row['value'], 'visitors' => $row['visitors'], 'pageviews' => $row['pageviews']], $rows);
                $data['technologies'][$dimension->value] = self::merge($data['technologies'][$dimension->value], $rows, ['value'], ['visitors', 'pageviews']);
            }
        }

        return ['daily' => $daily, ...$data];
    }

    /**
     * The chart: per day for ranges up to Analytics::DAILY_CHART_MAX_DAYS, per month beyond,
     * with the comparison period aligned point by point.
     *
     * @param  array<string, array<string, int>>  $current
     * @param  array<string, array<string, int>>  $previous
     * @return list<array{label: string, date: string, visitors: int, previous: int, pageviews: int}>
     */
    private function series(array $current, array $previous, int $days): array
    {
        $bucket = function (array $daily) use ($days): array {
            if ($days <= Analytics::DAILY_CHART_MAX_DAYS) {
                return array_map(fn (string $day, array $row): array => ['key' => $day, 'visitors' => $row['visitors'], 'pageviews' => $row['pageviews']], array_keys($daily), $daily);
            }

            $months = [];

            foreach ($daily as $day => $row) {
                $key = substr($day, 0, 7);
                $months[$key] ??= ['key' => $key, 'visitors' => 0, 'pageviews' => 0];
                $months[$key]['visitors'] += $row['visitors'];
                $months[$key]['pageviews'] += $row['pageviews'];
            }

            return array_values($months);
        };

        $now = $bucket($current);
        $before = $bucket($previous);

        return array_map(fn (array $point, int $index): array => [
            'label' => strlen($point['key']) === 7 ? CarbonImmutable::parse($point['key'].'-01')->format('M Y') : CarbonImmutable::parse($point['key'])->format('j M'),
            'date' => $point['key'],
            'visitors' => $point['visitors'],
            'previous' => $before[$index]['visitors'] ?? 0,
            'pageviews' => $point['pageviews'],
        ], $now, array_keys($now));
    }

    /**
     * Pages ranked by total engaged time (then views), optionally within one section.
     *
     * @param  list<array<string, mixed>>  $pages
     * @return list<array{path: string, label: string, url: string, section: string, section_label: string, views: int, visitors: int, engaged_seconds: int, average_engaged: float, average_scroll: float}>
     */
    private function pages(array $pages, ?string $section = null): array
    {
        $sections = Analytics::sections();
        $rows = [];

        foreach ($pages as $page) {
            if ($section !== null && $page['section'] !== $section) {
                continue;
            }

            $views = max(1, (int) $page['views']);
            $rows[] = [
                'path' => $page['path'],
                'label' => $page['title'] ?? $page['path'],
                'url' => url($page['path']),
                'section' => $page['section'],
                'section_label' => $sections[$page['section']] ?? $page['section'],
                'views' => (int) $page['views'],
                'visitors' => (int) $page['visitors'],
                'engaged_seconds' => (int) $page['engaged_seconds'],
                'average_engaged' => $page['engaged_seconds'] / $views,
                'average_scroll' => $page['scroll_depth_total'] / $views,
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$b['engaged_seconds'], $b['views'], $a['path']] <=> [$a['engaged_seconds'], $a['views'], $b['path']]);

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{key: string, label: string, flag: string, visitors: int, pageviews: int, share: float}>
     */
    private function referrers(array $rows): array
    {
        return $this->ranked($rows, 'referrer_domain', fn (string $domain): string => $domain === '' ? Analytics::DIRECT_LABEL : $domain);
    }

    /**
     * Rows ranked by visitors, each with its share of all visitors in the list.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  callable(string): string  $label
     * @param  (callable(string): string)|null  $flag
     * @return list<array{key: string, label: string, flag: string, visitors: int, pageviews: int, share: float}>
     */
    private function ranked(array $rows, string $key, callable $label, ?callable $flag = null): array
    {
        $total = array_sum(array_column($rows, 'visitors'));
        $ranked = array_map(fn (array $row): array => [
            'key' => (string) $row[$key],
            'label' => $label((string) $row[$key]),
            'flag' => $flag === null ? '' : $flag((string) $row[$key]),
            'visitors' => (int) $row['visitors'],
            'pageviews' => (int) $row['pageviews'],
            'share' => $total > 0 ? $row['visitors'] / $total * 100 : 0.0,
        ], $rows);

        usort($ranked, fn (array $a, array $b): int => [$b['visitors'], $b['pageviews'], $a['label']] <=> [$a['visitors'], $a['pageviews'], $b['label']]);

        return $ranked;
    }

    /**
     * Sum two lists of rows that share key columns.
     *
     * @param  list<array<string, mixed>>  $base
     * @param  list<array<string, mixed>>  $extra
     * @param  list<string>  $keys
     * @param  list<string>  $sums
     * @return list<array<string, mixed>>
     */
    private static function merge(array $base, array $extra, array $keys, array $sums): array
    {
        $merged = [];

        foreach ([...$base, ...$extra] as $row) {
            $id = implode("\0", array_map(fn (string $key): string => (string) $row[$key], $keys));

            if (! isset($merged[$id])) {
                $merged[$id] = $row;

                continue;
            }

            foreach ($sums as $sum) {
                $merged[$id][$sum] += $row[$sum];
            }

            foreach ($row as $column => $value) {
                if (! in_array($column, $sums, true) && $value !== null) {
                    $merged[$id][$column] = $value;
                }
            }
        }

        return array_values($merged);
    }
}
