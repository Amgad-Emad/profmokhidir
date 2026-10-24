<?php

namespace Tests\Unit\Repositories;

use App\Models\AnalyticsSalt;
use App\Repositories\Contracts\AnalyticsSaltRepositoryInterface;
use App\Repositories\Eloquent\EloquentAnalyticsSaltRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentAnalyticsSaltRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function salts_are_one_per_day_and_older_days_can_be_deleted(): void
    {
        $repository = $this->app->make(AnalyticsSaltRepositoryInterface::class);
        $this->assertInstanceOf(EloquentAnalyticsSaltRepository::class, $repository);

        $first = $repository->firstOrCreate(now(), str_repeat('a', 64));
        $second = $repository->firstOrCreate(now(), str_repeat('b', 64));
        AnalyticsSalt::factory()->yesterday()->create();
        AnalyticsSalt::factory()->forDay(now()->subDays(5))->create();

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(str_repeat('a', 64), $repository->forDay(now())?->salt);
        $this->assertSame(3, $repository->count());
        $this->assertSame(2, $repository->deleteBefore(now()));
        $this->assertSame(1, $repository->count());
        $this->assertNull($repository->forDay(now()->subDay()));
    }
}
