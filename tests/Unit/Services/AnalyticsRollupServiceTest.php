<?php

namespace Tests\Unit\Services;

use App\Constants\DeviceType;
use App\Models\AnalyticsDailyPage;
use App\Models\AnalyticsDailyTotal;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSalt;
use App\Services\AnalyticsRollupService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsRollupServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsRollupService $service;

    private CarbonImmutable $day;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(AnalyticsRollupService::class);
        $this->day = CarbonImmutable::parse('2026-10-20');
    }

    /**
     * Visitor A: two visits (09:00 from google with two pages, 14:00 direct); visitor B: one view from facebook on a campaign.
     */
    private function seedDay(): void
    {
        $a = str_repeat('a', 64);
        $b = str_repeat('b', 64);

        AnalyticsEvent::factory()->forVisitor($a)->at($this->day->setTime(9, 0))->page('/', 'Home')->from('google.com')->country('SD')->device(DeviceType::Mobile, 'Safari')->engaged(40, 50)->create();
        AnalyticsEvent::factory()->forVisitor($a)->at($this->day->setTime(9, 5))->page('/about', 'About Prof. Mokhidir')->from(null)->country('SD')->device(DeviceType::Mobile, 'Safari')->engaged(200, 100)->create();
        AnalyticsEvent::factory()->forVisitor($a)->at($this->day->setTime(14, 0))->page('/about', 'About Prof. Mokhidir')->from(null)->country('SD')->device(DeviceType::Mobile, 'Safari')->engaged(100, 75)->create();
        AnalyticsEvent::factory()->forVisitor($b)->at($this->day->setTime(10, 0))->page('/about', 'About Prof. Mokhidir')->from('facebook.com')->campaign('newsletter')->country('GB')->device(DeviceType::Desktop, 'Firefox')->engaged(60, 25)->create();
        AnalyticsEvent::factory()->at($this->day->subDay()->setTime(23, 0))->create();
    }

    #[Test]
    public function a_day_is_aggregated_into_visits_pages_countries_referrers_and_technologies(): void
    {
        $this->seedDay();

        $rollup = $this->service->aggregate($this->day);

        $this->assertSame(['visitors' => 2, 'returning_visitors' => 1, 'visits' => 3, 'pageviews' => 4, 'engaged_seconds' => 400], $rollup['totals']);

        $pages = collect($rollup['pages'])->keyBy('path');
        $this->assertSame(['path' => '/about', 'title' => 'About Prof. Mokhidir', 'section' => 'about', 'views' => 3, 'visitors' => 2, 'engaged_seconds' => 360, 'scroll_depth_total' => 200], $pages['/about']);
        $this->assertSame(1, $pages['/']['views']);

        $this->assertEqualsCanonicalizing([['country' => 'SD', 'visitors' => 1, 'pageviews' => 3], ['country' => 'GB', 'visitors' => 1, 'pageviews' => 1]], $rollup['countries']);

        // Referrers belong to the visit: A's google visit has two pages, A's afternoon visit is direct.
        $this->assertEqualsCanonicalizing([
            ['referrer_domain' => 'google.com', 'visitors' => 1, 'pageviews' => 2],
            ['referrer_domain' => '', 'visitors' => 1, 'pageviews' => 1],
            ['referrer_domain' => 'facebook.com', 'visitors' => 1, 'pageviews' => 1],
        ], $rollup['referrers']);

        $technologies = collect($rollup['technologies'])->map(fn (array $row): string => $row['dimension'].':'.$row['value'].':'.$row['visitors'].':'.$row['pageviews'])->all();
        $this->assertEqualsCanonicalizing(['device:mobile:1:3', 'device:desktop:1:1', 'browser:Safari:1:3', 'browser:Firefox:1:1', 'utm_source:newsletter:1:1'], $technologies);
    }

    #[Test]
    public function an_empty_day_rolls_up_to_zeros(): void
    {
        $this->assertSame(['visitors' => 0, 'returning_visitors' => 0, 'visits' => 0, 'pageviews' => 0, 'engaged_seconds' => 0], $this->service->aggregate($this->day)['totals']);
    }

    #[Test]
    public function pending_days_are_rolled_up_once_and_yesterday_always_again(): void
    {
        $this->seedDay();
        AnalyticsEvent::factory()->at($this->day->addDays(2)->setTime(8, 0))->create();

        $this->assertSame(['2026-10-19', '2026-10-20'], $this->service->rollUpPending($this->day->addDay()));
        $this->assertSame(['2026-10-20'], $this->service->rollUpPending($this->day->addDay()));
        $this->assertSame(['2026-10-22'], $this->service->rollUpPending($this->day->addDays(3)));

        $this->assertSame(3, AnalyticsDailyTotal::query()->count());
        $this->assertSame(2, AnalyticsDailyTotal::query()->whereDate('day', '2026-10-20')->sole()->visitors);
        $this->assertSame(1, AnalyticsDailyPage::query()->whereDate('day', '2026-10-20')->where('path', '/')->sole()->views);
    }

    #[Test]
    public function retention_deletes_raw_events_after_30_days_rollups_after_25_months_and_old_salts(): void
    {
        $now = CarbonImmutable::parse('2026-10-24 01:00:00');
        Carbon::setTestNow($now);

        AnalyticsEvent::factory()->at($now->subDays(31))->create();
        $kept = AnalyticsEvent::factory()->at($now->subDays(29))->create();
        AnalyticsDailyTotal::factory()->forDay($now->subMonths(25)->subDay())->create();
        $recent = AnalyticsDailyTotal::factory()->forDay($now->subMonths(24))->create();
        AnalyticsDailyPage::factory()->forDay($now->subMonths(26))->create();
        AnalyticsSalt::factory()->yesterday()->create();
        AnalyticsSalt::factory()->create();

        $this->assertSame(['events' => 1, 'rollups' => 2, 'salts' => 1], $this->service->prune($now));
        $this->assertSame([$kept->getKey()], AnalyticsEvent::query()->pluck('id')->all());
        $this->assertSame([$recent->getKey()], AnalyticsDailyTotal::query()->pluck('id')->all());
        $this->assertSame(1, AnalyticsSalt::query()->count());

        Carbon::setTestNow();
    }
}
