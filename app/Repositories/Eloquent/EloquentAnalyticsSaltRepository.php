<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\AnalyticsSalt;
use App\Repositories\Contracts\AnalyticsSaltRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;

class EloquentAnalyticsSaltRepository implements AnalyticsSaltRepositoryInterface
{
    public function __construct(private readonly AnalyticsSalt $model) {}

    public function forDay(CarbonInterface $day): ?AnalyticsSalt
    {
        return $this->model->newQuery()->whereDate('day', $day->toDateString())->first();
    }

    public function firstOrCreate(CarbonInterface $day, string $salt): AnalyticsSalt
    {
        try {
            return $this->model->newQuery()->firstOrCreate(['day' => $day->toDateString()], ['salt' => $salt]);
        } catch (UniqueConstraintViolationException) {
            // Two first page views of the day at once: the other request's salt wins.
            return $this->forDay($day) ?? throw new \RuntimeException('The analytics salt could not be stored.');
        }
    }

    public function deleteBefore(CarbonInterface $day): int
    {
        return $this->model->newQuery()->whereDate('day', '<', $day->toDateString())->delete();
    }

    public function count(): int
    {
        return $this->model->newQuery()->count();
    }
}
