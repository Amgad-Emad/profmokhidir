<?php

namespace Tests\Unit\Repositories;

use App\Models\AnalyticsEvent;
use App\Repositories\Contracts\AnalyticsEventRepositoryInterface;
use App\Repositories\Eloquent\EloquentAnalyticsEventRepository;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentAnalyticsEventRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsEventRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(AnalyticsEventRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentAnalyticsEventRepository::class, $this->repository);
    }

    #[Test]
    public function engagement_only_rises_and_only_for_the_views_own_visitor(): void
    {
        $event = AnalyticsEvent::factory()->engaged(30, 25)->create();

        $this->assertTrue($this->repository->recordEngagement($event->view_id, $event->visitor_hash, 50, 75));
        $this->assertTrue($this->repository->recordEngagement($event->view_id, $event->visitor_hash, 10, 50));
        $this->assertFalse($this->repository->recordEngagement($event->view_id, str_repeat('0', 64), 900, 100));
        $this->assertFalse($this->repository->recordEngagement((string) Str::uuid(), $event->visitor_hash, 900, 100));

        $event->refresh();
        $this->assertSame(50, $event->engaged_seconds);
        $this->assertSame(75, $event->scroll_depth);
        $this->assertSame($event->getKey(), $this->repository->findByView($event->view_id)?->getKey());
    }

    #[Test]
    public function for_day_returns_one_day_oldest_first_and_days_before_lists_earlier_days(): void
    {
        $day = CarbonImmutable::parse('2026-10-20');
        $late = AnalyticsEvent::factory()->at($day->setTime(23, 59))->create();
        $early = AnalyticsEvent::factory()->at($day->setTime(0, 0))->create();
        AnalyticsEvent::factory()->at($day->subDays(2)->setTime(12, 0))->create();
        AnalyticsEvent::factory()->at($day->addDay()->setTime(0, 0))->create();

        $this->assertSame([$early->path, $late->path], $this->repository->forDay($day)->pluck('path')->all());
        $this->assertSame(['2026-10-18', '2026-10-20'], $this->repository->daysBefore($day->addDay()));
        $this->assertSame(4, $this->repository->count());
    }

    #[Test]
    public function insert_many_and_delete_before(): void
    {
        $rows = AnalyticsEvent::factory()->count(3)->make()->map(fn (AnalyticsEvent $event): array => [...$event->getAttributes(), 'occurred_at' => now()->subDays(40)->toDateTimeString()])->all();

        $this->assertSame(3, $this->repository->insertMany($rows));
        AnalyticsEvent::factory()->create();

        $this->assertSame(3, $this->repository->deleteBefore(now()->subDays(30)));
        $this->assertSame(1, $this->repository->count());
    }
}
