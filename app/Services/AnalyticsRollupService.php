<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Analytics;
use App\Constants\AnalyticsDimension;
use App\Models\AnalyticsEvent;
use App\Repositories\Contracts\AnalyticsEventRepositoryInterface;
use App\Repositories\Contracts\AnalyticsRollupRepositoryInterface;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns a day of raw page views into the daily rollups, and applies the
 * retention rules: raw events go after 30 days, rollups after 25 months, and
 * old visitor-hash salts are deleted. Runs nightly (App\Jobs\RollUpAnalytics).
 *
 * A visit is a visitor's run of page views with no gap longer than
 * Analytics::VISIT_GAP_MINUTES; a returning visitor made more than one visit
 * that day. Referrers and UTM sources belong to the visit (its first page
 * view), so pages reached by clicking around the site do not count as direct.
 */
class AnalyticsRollupService
{
    public function __construct(
        private readonly AnalyticsEventRepositoryInterface $events,
        private readonly AnalyticsRollupRepositoryInterface $rollups,
        private readonly VisitorHashService $hashes,
    ) {}

    /**
     * The rollup of one day, computed from its raw page views (nothing is stored).
     *
     * @return array{totals: array{visitors: int, returning_visitors: int, visits: int, pageviews: int, engaged_seconds: int}, pages: list<array{path: string, title: string|null, section: string, views: int, visitors: int, engaged_seconds: int, scroll_depth_total: int}>, countries: list<array{country: string, visitors: int, pageviews: int}>, referrers: list<array{referrer_domain: string, visitors: int, pageviews: int}>, technologies: list<array{dimension: string, value: string, visitors: int, pageviews: int}>}
     */
    public function aggregate(CarbonInterface $day): array
    {
        return self::aggregateEvents($this->events->forDay($day));
    }

    /**
     * @param  Collection<int, AnalyticsEvent>  $events  one day's page views, oldest first
     * @return array{totals: array{visitors: int, returning_visitors: int, visits: int, pageviews: int, engaged_seconds: int}, pages: list<array{path: string, title: string|null, section: string, views: int, visitors: int, engaged_seconds: int, scroll_depth_total: int}>, countries: list<array{country: string, visitors: int, pageviews: int}>, referrers: list<array{referrer_domain: string, visitors: int, pageviews: int}>, technologies: list<array{dimension: string, value: string, visitors: int, pageviews: int}>}
     */
    public static function aggregateEvents(Collection $events): array
    {
        $pages = [];
        $countries = [];
        $referrers = [];
        $technologies = [];
        $visits = 0;
        $returning = 0;

        foreach ($events as $event) {
            $hash = $event->visitor_hash;
            $page = &$pages[$event->path];
            $page ??= ['path' => $event->path, 'title' => null, 'section' => $event->section, 'views' => 0, 'visitors' => [], 'engaged_seconds' => 0, 'scroll_depth_total' => 0];
            $page['title'] = $event->title ?? $page['title'];
            $page['views']++;
            $page['visitors'][$hash] = true;
            $page['engaged_seconds'] += $event->engaged_seconds;
            $page['scroll_depth_total'] += $event->scroll_depth;
            unset($page);

            self::count($countries, $event->country, $hash);
            self::count($technologies, AnalyticsDimension::Device->value."\0".$event->device->value, $hash);
            self::count($technologies, AnalyticsDimension::Browser->value."\0".$event->browser, $hash);
        }

        foreach ($events->groupBy('visitor_hash') as $hash => $views) {
            $sessions = self::visits($views);
            $visits += count($sessions);
            $returning += count($sessions) > 1 ? 1 : 0;

            foreach ($sessions as $session) {
                $first = $session[0];
                $utm = collect($session)->first(fn (AnalyticsEvent $view): bool => $view->utm_source !== null)?->utm_source;

                self::count($referrers, (string) $first->referrer_domain, (string) $hash, count($session));

                if ($utm !== null) {
                    self::count($technologies, AnalyticsDimension::UtmSource->value."\0".$utm, (string) $hash, count($session));
                }
            }
        }

        return [
            'totals' => [
                'visitors' => $events->pluck('visitor_hash')->unique()->count(),
                'returning_visitors' => $returning,
                'visits' => $visits,
                'pageviews' => $events->count(),
                'engaged_seconds' => (int) $events->sum('engaged_seconds'),
            ],
            'pages' => array_values(array_map(fn (array $page): array => [...$page, 'visitors' => count($page['visitors'])], $pages)),
            'countries' => self::rows($countries, fn (string $key): array => ['country' => $key]),
            'referrers' => self::rows($referrers, fn (string $key): array => ['referrer_domain' => $key]),
            'technologies' => self::rows($technologies, function (string $key): array {
                [$dimension, $value] = explode("\0", $key, 2);

                return ['dimension' => $dimension, 'value' => $value];
            }),
        ];
    }

    /**
     * Store the rollup of one day (replacing any earlier rollup of it).
     */
    public function rollUp(CarbonInterface $day): void
    {
        $this->rollups->replaceDay($day, $this->aggregate($day));
    }

    /**
     * Roll up every finished day that has raw events and no rollup yet, and
     * always yesterday again (beacons sent after midnight for a page opened
     * before it). Returns the days rolled up.
     *
     * @return list<string>
     */
    public function rollUpPending(?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::parse(($today ?? Carbon::now())->toDateString());
        $yesterday = $today->subDay()->toDateString();
        $done = [];

        foreach ($this->events->daysBefore($today) as $day) {
            $date = CarbonImmutable::parse($day);

            if ($day === $yesterday || ! $this->rollups->hasDay($date)) {
                $this->rollUp($date);
                $done[] = $day;
            }
        }

        return $done;
    }

    /**
     * Apply the retention rules. Returns how many rows went from each place.
     *
     * @return array{events: int, rollups: int, salts: int}
     */
    public function prune(?CarbonInterface $now = null): array
    {
        $now = CarbonImmutable::parse(($now ?? Carbon::now())->toDateTimeString());

        return [
            'events' => $this->events->deleteBefore($now->subDays(Analytics::RAW_RETENTION_DAYS)),
            'rollups' => $this->rollups->deleteBefore($now->startOfDay()->subMonthsNoOverflow(Analytics::ROLLUP_RETENTION_MONTHS)),
            'salts' => $this->hashes->forgetBefore($now),
        ];
    }

    /**
     * Split one visitor's page views into visits.
     *
     * @param  Collection<int, AnalyticsEvent>  $views  oldest first
     * @return list<list<AnalyticsEvent>>
     */
    private static function visits(Collection $views): array
    {
        $visits = [];
        $last = null;

        foreach ($views as $view) {
            if ($last === null || $last->diffInMinutes($view->occurred_at) > Analytics::VISIT_GAP_MINUTES) {
                $visits[] = [];
            }

            $visits[count($visits) - 1][] = $view;
            $last = $view->occurred_at;
        }

        return $visits;
    }

    /**
     * @param  array<string, array{visitors: array<string, true>, pageviews: int}>  $bucket
     */
    private static function count(array &$bucket, string $key, string $hash, int $pageviews = 1): void
    {
        $bucket[$key] ??= ['visitors' => [], 'pageviews' => 0];
        $bucket[$key]['visitors'][$hash] = true;
        $bucket[$key]['pageviews'] += $pageviews;
    }

    /**
     * @param  array<string, array{visitors: array<string, true>, pageviews: int}>  $bucket
     * @param  callable(string): array<string, string>  $columns
     * @return list<array<string, int|string>>
     */
    private static function rows(array $bucket, callable $columns): array
    {
        $rows = [];

        foreach ($bucket as $key => $counts) {
            $rows[] = [...$columns((string) $key), 'visitors' => count($counts['visitors']), 'pageviews' => $counts['pageviews']];
        }

        return $rows;
    }
}
