<?php

namespace Tests\Unit\Services;

use App\Constants\Analytics;
use App\Constants\AnalyticsRange;
use App\Constants\DeviceType;
use App\Constants\Sections;
use App\Models\AnalyticsDailyCountry;
use App\Models\AnalyticsDailyPage;
use App\Models\AnalyticsDailyReferrer;
use App\Models\AnalyticsDailyTechnology;
use App\Models\AnalyticsDailyTotal;
use App\Models\AnalyticsEvent;
use App\Services\AnalyticsReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsReportService $service;

    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();

        $this->today = CarbonImmutable::parse('2026-10-24');
        Carbon::setTestNow($this->today->setTime(15, 0));
        $this->service = $this->app->make(AnalyticsReportService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function the_period_and_its_comparison_have_the_same_length(): void
    {
        $period = $this->service->period(AnalyticsRange::Week, today: $this->today);

        $this->assertSame('2026-10-18', $period['from']->toDateString());
        $this->assertSame(7, $period['days']);
        $this->assertSame('2026-10-11', $period['previous_from']->toDateString());
        $this->assertSame('2026-10-17', $period['previous_to']->toDateString());
        $this->assertSame('the previous 7 days', $period['comparison']);
        $this->assertSame('yesterday', $this->service->period(AnalyticsRange::Today, today: $this->today)['comparison']);

        $custom = $this->service->period(AnalyticsRange::Custom, '2026-10-01', '2026-10-05', $this->today);
        $this->assertSame(5, $custom['days']);
        $this->assertSame('2026-09-26', $custom['previous_from']->toDateString());
    }

    #[Test]
    public function headline_figures_compare_with_the_previous_period(): void
    {
        AnalyticsDailyTotal::factory()->forDay($this->today->subDay())->create(['visitors' => 30, 'returning_visitors' => 3, 'visits' => 40, 'pageviews' => 90, 'engaged_seconds' => 4000]);
        AnalyticsDailyTotal::factory()->forDay($this->today->subDays(8))->create(['visitors' => 20, 'returning_visitors' => 4, 'visits' => 20, 'pageviews' => 90, 'engaged_seconds' => 2000]);

        $report = $this->service->report($this->service->period(AnalyticsRange::Week, today: $this->today));
        $headline = $report['headline'];

        $this->assertSame(30, $headline['visitors']['value']);
        $this->assertEqualsWithDelta(50.0, $headline['visitors']['change'], 0.001);
        $this->assertEqualsWithDelta(0.0, $headline['pageviews']['change'], 0.001);
        $this->assertSame('1m 40s', $headline['engaged']['display']);
        $this->assertEqualsWithDelta(10.0, $headline['returning']['value'], 0.001);
        $this->assertSame('10%', $headline['returning']['display']);
        $this->assertCount(7, $report['series']);
        $this->assertSame(30, $report['series'][5]['visitors']);
        $this->assertSame(20, $report['series'][5]['previous']);
        $this->assertTrue($report['hasData']);
        $this->assertFalse($report['enabled']);
    }

    #[Test]
    public function today_comes_live_from_the_raw_events_and_merges_with_the_rollups(): void
    {
        AnalyticsDailyTotal::factory()->forDay($this->today->subDay())->create(['visitors' => 5, 'returning_visitors' => 0, 'visits' => 5, 'pageviews' => 5, 'engaged_seconds' => 100]);
        AnalyticsDailyPage::factory()->forDay($this->today->subDay())->page('/about', 'About Prof. Mokhidir')->create(['views' => 5, 'visitors' => 5, 'engaged_seconds' => 100, 'scroll_depth_total' => 250]);
        AnalyticsDailyCountry::factory()->forDay($this->today->subDay())->country('SD')->create(['visitors' => 5, 'pageviews' => 5]);
        AnalyticsEvent::factory()->at($this->today->setTime(9, 0))->page('/about', 'About Prof. Mokhidir')->country('SD')->engaged(300, 100)->create();
        AnalyticsEvent::factory()->at($this->today->setTime(10, 0))->page('/publications', 'Publications')->country('EG')->device(DeviceType::Tablet)->from('google.com')->engaged(20, 25)->create();

        $report = $this->service->report($this->service->period(AnalyticsRange::Week, today: $this->today));

        $this->assertSame(7, $report['headline']['visitors']['value']);
        $this->assertSame(2, end($report['series'])['visitors']);

        $about = collect($report['pages'])->firstWhere('path', '/about');
        $this->assertSame(6, $about['views']);
        $this->assertSame(400, $about['engaged_seconds']);
        $this->assertEqualsWithDelta(400 / 6, $about['average_engaged'], 0.001);
        $this->assertEqualsWithDelta(350 / 6, $about['average_scroll'], 0.001);
        $this->assertSame('About Prof. Mokhidir', $about['label']);
        $this->assertSame(url('/about'), $about['url']);
        $this->assertSame('/about', $report['pages'][0]['path']);

        $this->assertSame(['SD', 'EG'], array_column($report['countries'], 'key'));
        $this->assertSame('Sudan', $report['countries'][0]['label']);
        $this->assertEqualsWithDelta(6 / 7 * 100, $report['countries'][0]['share'], 0.001);
        $this->assertSame(2, $report['countryCount']);
        $this->assertSame(5, collect($report['map'])->firstWhere('key', 'SD')['tier']);

        $this->assertSame(['Tablet'], array_column(array_filter($report['devices'], fn (array $row): bool => $row['key'] === 'tablet'), 'label'));
        $this->assertSame('google.com', collect($report['referrers'])->firstWhere('key', 'google.com')['label']);
    }

    #[Test]
    public function the_pages_table_filters_by_section_and_ranks_by_engaged_time(): void
    {
        AnalyticsDailyPage::factory()->forDay($this->today->subDay())->page('/publications', 'Publications')->create(['views' => 50, 'engaged_seconds' => 100]);
        AnalyticsDailyPage::factory()->forDay($this->today->subDay())->page('/commentary/a', 'An essay')->create(['views' => 2, 'engaged_seconds' => 900]);
        AnalyticsDailyPage::factory()->forDay($this->today->subDay())->page('/commentary', 'Commentary Corner')->create(['views' => 9, 'engaged_seconds' => 300]);

        $period = $this->service->period(AnalyticsRange::Week, today: $this->today);

        $this->assertSame(['/commentary/a', '/commentary', '/publications'], array_column($this->service->report($period)['pages'], 'path'));
        $this->assertSame(['/commentary/a', '/commentary'], array_column($this->service->report($period, Sections::COMMENTARY)['pages'], 'path'));
        $this->assertSame('Commentary', $this->service->report($period, Sections::COMMENTARY)['pages'][0]['section_label']);
    }

    #[Test]
    public function long_ranges_are_charted_per_month(): void
    {
        $report = $this->service->report($this->service->period(AnalyticsRange::Year, today: $this->today));

        $this->assertCount(13, $report['series']);
        $this->assertSame('Oct 2025', $report['series'][0]['label']);
        $this->assertFalse($report['hasData']);
        $this->assertNull($report['headline']['visitors']['change']);
    }

    #[Test]
    public function csv_exports_give_every_row_with_a_header(): void
    {
        AnalyticsDailyTotal::factory()->forDay($this->today->subDay())->create(['visitors' => 4, 'returning_visitors' => 1, 'visits' => 5, 'pageviews' => 9, 'engaged_seconds' => 300]);
        AnalyticsDailyReferrer::factory()->forDay($this->today->subDay())->direct()->create(['visitors' => 3, 'pageviews' => 6]);
        AnalyticsDailyReferrer::factory()->forDay($this->today->subDay())->create(['referrer_domain' => 'google.com', 'visitors' => 1, 'pageviews' => 3]);
        AnalyticsDailyTechnology::factory()->forDay($this->today->subDay())->device(DeviceType::Mobile)->create(['visitors' => 4, 'pageviews' => 9]);
        AnalyticsDailyPage::factory()->forDay($this->today->subDay())->page('/about', 'About Prof. Mokhidir')->create(['views' => 4, 'visitors' => 4, 'engaged_seconds' => 200, 'scroll_depth_total' => 300]);

        $period = $this->service->period(AnalyticsRange::Week, today: $this->today);

        $visitors = $this->service->csv(Analytics::EXPORT_VISITORS, $period);
        $this->assertSame(['Date', 'Unique visitors', 'Returning visitors', 'Visits', 'Page views', 'Engaged seconds'], $visitors[0]);
        $this->assertCount(8, $visitors);
        $this->assertSame(['2026-10-23', 4, 1, 5, 9, 300], $visitors[6]);

        $this->assertSame([['Referrer', 'Visitors', 'Share (%)', 'Page views'], [Analytics::DIRECT_LABEL, 3, '75.0', 6], ['google.com', 1, '25.0', 3]], $this->service->csv(Analytics::EXPORT_REFERRERS, $period));
        $this->assertSame(['Mobile', 4, '100.0', 9], $this->service->csv(Analytics::EXPORT_DEVICES, $period)[1]);
        $this->assertSame(['About Prof. Mokhidir', '/about', 'About', 200, 50.0, 4, 75.0], $this->service->csv(Analytics::EXPORT_PAGES, $period)[1]);
        $this->assertSame(['Country code', 'Country', 'Visitors', 'Share (%)', 'Page views'], $this->service->csv(Analytics::EXPORT_COUNTRIES, $period)[0]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->csv('nope', $period);
    }

    #[Test]
    public function the_audience_summary_is_the_last_30_days_with_three_pages_and_countries(): void
    {
        foreach (['SD', 'SA', 'EG', 'GB'] as $index => $code) {
            AnalyticsDailyCountry::factory()->forDay($this->today->subDays(2))->country($code)->create(['visitors' => 10 - $index]);
            AnalyticsDailyPage::factory()->forDay($this->today->subDays(2))->page('/p'.$index, 'Page '.$index)->create(['engaged_seconds' => 100 - $index]);
        }
        AnalyticsDailyTotal::factory()->forDay($this->today->subDays(2))->create();

        $audience = $this->service->audience($this->today);

        $this->assertSame(30, $audience['period']['days']);
        $this->assertSame(['SD', 'SA', 'EG'], array_column($audience['countries'], 'key'));
        $this->assertSame(['/p0', '/p1', '/p2'], array_column($audience['pages'], 'path'));
        $this->assertSame(['visitors', 'pageviews', 'engaged', 'returning'], array_keys($audience['headline']));
        $this->assertTrue($audience['hasData']);
    }
}
