<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\AnalyticsDimension;
use App\Models\AnalyticsDailyCountry;
use App\Models\AnalyticsDailyPage;
use App\Models\AnalyticsDailyReferrer;
use App\Models\AnalyticsDailyTechnology;
use App\Models\AnalyticsDailyTotal;
use App\Repositories\Contracts\AnalyticsRollupRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EloquentAnalyticsRollupRepository implements AnalyticsRollupRepositoryInterface
{
    public function __construct(
        private readonly AnalyticsDailyTotal $totals,
        private readonly AnalyticsDailyPage $pages,
        private readonly AnalyticsDailyCountry $countries,
        private readonly AnalyticsDailyReferrer $referrers,
        private readonly AnalyticsDailyTechnology $technologies,
    ) {}

    public function replaceDay(CarbonInterface $day, array $rollup): void
    {
        $date = $day->toDateString();
        $now = now();

        DB::transaction(function () use ($date, $now, $rollup): void {
            foreach ($this->models() as $model) {
                $model->newQuery()->whereDate('day', $date)->delete();
            }

            $this->totals->newQuery()->insert(['day' => $date, ...$rollup['totals'], 'created_at' => $now, 'updated_at' => $now]);

            foreach ([[$this->pages, 'pages'], [$this->countries, 'countries'], [$this->referrers, 'referrers'], [$this->technologies, 'technologies']] as [$model, $key]) {
                foreach (array_chunk($rollup[$key], 500) as $chunk) {
                    $model->newQuery()->insert(array_map(fn (array $row): array => ['day' => $date, ...$row, 'created_at' => $now, 'updated_at' => $now], $chunk));
                }
            }
        });
    }

    public function hasDay(CarbonInterface $day): bool
    {
        return $this->totals->newQuery()->whereDate('day', $day->toDateString())->exists();
    }

    public function dailyTotals(CarbonInterface $from, CarbonInterface $to): array
    {
        $days = [];

        foreach ($this->between($this->totals, $from, $to)->orderBy('day')->get() as $row) {
            $days[$row->day->toDateString()] = [
                'visitors' => $row->visitors,
                'returning_visitors' => $row->returning_visitors,
                'visits' => $row->visits,
                'pageviews' => $row->pageviews,
                'engaged_seconds' => $row->engaged_seconds,
            ];
        }

        return $days;
    }

    public function pages(CarbonInterface $from, CarbonInterface $to, ?string $section = null): array
    {
        return $this->between($this->pages, $from, $to)
            ->when($section !== null, fn (Builder $query) => $query->where('section', $section))
            ->groupBy('path')
            ->selectRaw('path, MAX(title) as title, MAX(section) as section, SUM(views) as views, SUM(visitors) as visitors, SUM(engaged_seconds) as engaged_seconds, SUM(scroll_depth_total) as scroll_depth_total')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'path' => (string) $row->path,
                'title' => $row->title === null ? null : (string) $row->title,
                'section' => (string) $row->section,
                'views' => (int) $row->views,
                'visitors' => (int) $row->visitors,
                'engaged_seconds' => (int) $row->engaged_seconds,
                'scroll_depth_total' => (int) $row->scroll_depth_total,
            ])
            ->all();
    }

    public function countries(CarbonInterface $from, CarbonInterface $to): array
    {
        return $this->summed($this->countries, 'country', $from, $to);
    }

    public function referrers(CarbonInterface $from, CarbonInterface $to): array
    {
        return $this->summed($this->referrers, 'referrer_domain', $from, $to);
    }

    public function technologies(AnalyticsDimension $dimension, CarbonInterface $from, CarbonInterface $to): array
    {
        return $this->summed($this->technologies, 'value', $from, $to, fn (Builder $query) => $query->where('dimension', $dimension->value));
    }

    public function deleteBefore(CarbonInterface $day): int
    {
        $deleted = 0;

        foreach ($this->models() as $model) {
            $deleted += $model->newQuery()->whereDate('day', '<', $day->toDateString())->delete();
        }

        return $deleted;
    }

    public function hasAny(): bool
    {
        return $this->totals->newQuery()->exists();
    }

    /**
     * @return list<Model>
     */
    private function models(): array
    {
        return [$this->totals, $this->pages, $this->countries, $this->referrers, $this->technologies];
    }

    private function between(Model $model, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $model->newQuery()->whereBetween('day', [$from->toDateString(), $to->toDateString()]);
    }

    /**
     * @return list<array<string, int|string>>
     */
    private function summed(Model $model, string $column, CarbonInterface $from, CarbonInterface $to, ?callable $scope = null): array
    {
        return $this->between($model, $from, $to)
            ->when($scope !== null, $scope)
            ->groupBy($column)
            ->selectRaw($column.', SUM(visitors) as visitors, SUM(pageviews) as pageviews')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                $column === 'value' ? 'value' : $column => (string) $row->{$column},
                'visitors' => (int) $row->visitors,
                'pageviews' => (int) $row->pageviews,
            ])
            ->all();
    }
}
