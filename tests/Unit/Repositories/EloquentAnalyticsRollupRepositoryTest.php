<?php

namespace Tests\Unit\Repositories;

use App\Constants\AnalyticsDimension;
use App\Constants\Sections;
use App\Models\AnalyticsDailyCountry;
use App\Models\AnalyticsDailyPage;
use App\Models\AnalyticsDailyReferrer;
use App\Models\AnalyticsDailyTechnology;
use App\Models\AnalyticsDailyTotal;
use App\Repositories\Contracts\AnalyticsRollupRepositoryInterface;
use App\Repositories\Eloquent\EloquentAnalyticsRollupRepository;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentAnalyticsRollupRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsRollupRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(AnalyticsRollupRepositoryInterface::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function rollup(int $visitors = 3): array
    {
        return [
            'totals' => ['visitors' => $visitors, 'returning_visitors' => 1, 'visits' => $visitors + 1, 'pageviews' => 7, 'engaged_seconds' => 300],
            'pages' => [
                ['path' => '/about', 'title' => 'About Prof. Mokhidir', 'section' => Sections::ABOUT, 'views' => 4, 'visitors' => 3, 'engaged_seconds' => 200, 'scroll_depth_total' => 300],
                ['path' => '/publications', 'title' => 'Publications', 'section' => Sections::PUBLICATIONS, 'views' => 3, 'visitors' => 2, 'engaged_seconds' => 100, 'scroll_depth_total' => 75],
            ],
            'countries' => [['country' => 'SD', 'visitors' => 2, 'pageviews' => 5], ['country' => 'GB', 'visitors' => 1, 'pageviews' => 2]],
            'referrers' => [['referrer_domain' => '', 'visitors' => 2, 'pageviews' => 4], ['referrer_domain' => 'google.com', 'visitors' => 1, 'pageviews' => 3]],
            'technologies' => [
                ['dimension' => 'device', 'value' => 'mobile', 'visitors' => 2, 'pageviews' => 5],
                ['dimension' => 'browser', 'value' => 'Safari', 'visitors' => 3, 'pageviews' => 7],
            ],
        ];
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentAnalyticsRollupRepository::class, $this->repository);
        $this->assertFalse($this->repository->hasAny());
    }

    #[Test]
    public function replacing_a_day_writes_every_table_once_and_reads_sum_the_range(): void
    {
        $first = CarbonImmutable::parse('2026-10-01');
        $second = $first->addDay();

        $this->repository->replaceDay($first, $this->rollup(9));
        $this->repository->replaceDay($first, $this->rollup());
        $this->repository->replaceDay($second, $this->rollup());

        $this->assertSame(2, AnalyticsDailyTotal::query()->count());
        $this->assertSame(4, AnalyticsDailyPage::query()->count());
        $this->assertTrue($this->repository->hasDay($first));
        $this->assertFalse($this->repository->hasDay($second->addDay()));

        $this->assertSame(['2026-10-01', '2026-10-02'], array_keys($this->repository->dailyTotals($first, $second)));
        $this->assertSame(3, $this->repository->dailyTotals($first, $first)['2026-10-01']['visitors']);

        $pages = collect($this->repository->pages($first, $second))->keyBy('path');
        $this->assertSame(8, $pages['/about']['views']);
        $this->assertSame(400, $pages['/about']['engaged_seconds']);
        $this->assertSame('About Prof. Mokhidir', $pages['/about']['title']);
        $this->assertSame(['/publications'], array_column($this->repository->pages($first, $second, Sections::PUBLICATIONS), 'path'));

        $this->assertSame(['country' => 'SD', 'visitors' => 4, 'pageviews' => 10], collect($this->repository->countries($first, $second))->firstWhere('country', 'SD'));
        $this->assertSame(['referrer_domain' => '', 'visitors' => 4, 'pageviews' => 8], collect($this->repository->referrers($first, $second))->firstWhere('referrer_domain', ''));
        $this->assertSame([['value' => 'Safari', 'visitors' => 6, 'pageviews' => 14]], $this->repository->technologies(AnalyticsDimension::Browser, $first, $second));
        $this->assertSame([], $this->repository->technologies(AnalyticsDimension::UtmSource, $first, $second));
    }

    #[Test]
    public function delete_before_removes_old_days_from_every_table(): void
    {
        $old = CarbonImmutable::parse('2024-01-01');
        AnalyticsDailyTotal::factory()->forDay($old)->create();
        AnalyticsDailyPage::factory()->forDay($old)->create();
        AnalyticsDailyCountry::factory()->forDay($old)->create();
        AnalyticsDailyReferrer::factory()->forDay($old)->create();
        AnalyticsDailyTechnology::factory()->forDay($old)->browser()->create();
        AnalyticsDailyTotal::factory()->create();

        $this->assertSame(5, $this->repository->deleteBefore(CarbonImmutable::parse('2024-06-01')));
        $this->assertSame(1, AnalyticsDailyTotal::query()->count());
        $this->assertTrue($this->repository->hasAny());
    }
}
