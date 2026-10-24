<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\AnalyticsEvent;
use App\Repositories\Contracts\AnalyticsEventRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentAnalyticsEventRepository implements AnalyticsEventRepositoryInterface
{
    public function __construct(private readonly AnalyticsEvent $model) {}

    public function create(array $attributes): AnalyticsEvent
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function insertMany(array $rows): int
    {
        $now = now();

        foreach (array_chunk($rows, 1000) as $chunk) {
            $this->model->newQuery()->insert(array_map(fn (array $row): array => [
                ...$row,
                'device' => $row['device'] instanceof \BackedEnum ? $row['device']->value : $row['device'],
                'created_at' => $row['created_at'] ?? $now,
                'updated_at' => $row['updated_at'] ?? $now,
            ], $chunk));
        }

        return count($rows);
    }

    public function findByView(string $viewId): ?AnalyticsEvent
    {
        return $this->model->newQuery()->where('view_id', $viewId)->first();
    }

    public function recordEngagement(string $viewId, string $visitorHash, int $engagedSeconds, int $scrollDepth): bool
    {
        $query = $this->model->newQuery()->where('view_id', $viewId)->where('visitor_hash', $visitorHash);

        // MySQL reports changed rows, not matched ones, so a beacon with nothing new would look unknown.
        if (! $query->clone()->exists()) {
            return false;
        }

        $query->update([
            'engaged_seconds' => DB::raw('GREATEST(engaged_seconds, '.max(0, $engagedSeconds).')'),
            'scroll_depth' => DB::raw('GREATEST(scroll_depth, '.max(0, $scrollDepth).')'),
            'updated_at' => now(),
        ]);

        return true;
    }

    public function forDay(CarbonInterface $day): Collection
    {
        return $this->model->newQuery()
            ->select(['visitor_hash', 'occurred_at', 'path', 'title', 'section', 'referrer_domain', 'country', 'device', 'browser', 'utm_source', 'engaged_seconds', 'scroll_depth'])
            ->where('occurred_at', '>=', $day->copy()->startOfDay())
            ->where('occurred_at', '<', $day->copy()->startOfDay()->addDay())
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    public function daysBefore(CarbonInterface $day): array
    {
        return $this->model->newQuery()
            ->where('occurred_at', '<', $day->copy()->startOfDay())
            ->selectRaw('DATE(occurred_at) as day')
            ->distinct()
            ->orderBy('day')
            ->pluck('day')
            ->map(fn (mixed $value): string => substr((string) $value, 0, 10))
            ->values()
            ->all();
    }

    public function deleteBefore(CarbonInterface $moment): int
    {
        return $this->model->newQuery()->where('occurred_at', '<', $moment)->delete();
    }

    public function count(): int
    {
        return $this->model->newQuery()->count();
    }
}
