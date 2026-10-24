<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\Analytics;
use App\Constants\SettingKeys;
use App\Models\AnalyticsDailyCountry;
use App\Models\AnalyticsDailyPage;
use App\Models\AnalyticsDailyTotal;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-24 12:00:00');
        AnalyticsDailyTotal::factory()->forDay(now()->subDay())->create(['visitors' => 42, 'returning_visitors' => 4, 'visits' => 50, 'pageviews' => 120, 'engaged_seconds' => 6000]);
        AnalyticsDailyPage::factory()->forDay(now()->subDay())->page('/commentary/on-soil', 'On soil and patience')->create(['views' => 30, 'engaged_seconds' => 5400, 'scroll_depth_total' => 2250]);
        AnalyticsDailyPage::factory()->forDay(now()->subDay())->page('/publications', 'Publications')->create(['views' => 60, 'engaged_seconds' => 600]);
        AnalyticsDailyCountry::factory()->forDay(now()->subDay())->country('SD')->create(['visitors' => 30, 'pageviews' => 90]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function admins_and_editors_may_open_it_and_everyone_else_may_not(): void
    {
        $this->assertSame(url('/admin/analytics'), route(Analytics::ADMIN_ROUTE));

        $this->get(route(Analytics::ADMIN_ROUTE))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get(route(Analytics::ADMIN_ROUTE))->assertOk();
        $this->actingAs(User::factory()->editor()->create())->get(route(Analytics::ADMIN_ROUTE))->assertOk();
        $this->actingAs(User::factory()->member()->create())->get(route(Analytics::ADMIN_ROUTE))->assertForbidden();
        $this->actingAs(User::factory()->member()->create())->get(route(Analytics::EXPORT_ROUTE, ['export' => Analytics::EXPORT_PAGES]))->assertForbidden();
        $this->actingAs(User::factory()->applicant()->create())->get(route(Analytics::ADMIN_ROUTE))->assertForbidden();

        $this->assertTrue(User::factory()->editor()->create()->can('viewAny', AnalyticsDailyTotal::class));
        $this->assertFalse(User::factory()->member()->create()->can('viewAny', AnalyticsDailyTotal::class));
    }

    #[Test]
    public function the_report_shows_figures_chart_table_map_pages_and_lists(): void
    {
        $response = $this->actingAs(User::factory()->editor()->create())->get(route(Analytics::ADMIN_ROUTE))->assertOk();

        $response->assertSee('data-nav-item="'.AdminNavigation::ANALYTICS.'"', false)
            ->assertSee('Unique visitors')->assertSee('42')
            ->assertSee('Page views')->assertSee('Engaged time per visit')->assertSee('2m 00s')
            ->assertSee('Returning visitors')->assertSee('10%')
            ->assertSee('No figures for the previous 30 days')
            ->assertSee('data-visitors-chart', false)
            ->assertSee('Show the figures as a table')
            ->assertSee('data-world-map', false)
            ->assertSee('data-country="SD"', false)
            ->assertSee('Sudan')
            ->assertSee('On soil and patience')
            ->assertSee('href="'.url('/commentary/on-soil').'"', false)
            ->assertSee('1h 30m')
            ->assertSee('75%')
            ->assertSee('data-ranked="referrers"', false)
            ->assertSee('data-ranked="devices"', false)
            ->assertSee('data-ranked="browsers"', false)
            ->assertSee('data-export="pages"', false)
            ->assertSee('data-analytics-off', false)
            ->assertSee('An administrator can switch it on');

        $this->assertLessThan(strpos($response->getContent(), 'Publications</a>'), strpos($response->getContent(), 'On soil and patience'));
    }

    #[Test]
    public function the_off_notice_goes_once_analytics_is_on_and_admins_are_pointed_to_settings(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route(Analytics::ADMIN_ROUTE))->assertSee('data-analytics-off', false)->assertSee(route(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS)).'#analytics', false);

        $this->app->make(SettingService::class)->update([SettingKeys::ANALYTICS_ENABLED => true]);
        $this->actingAs($admin)->get(route(Analytics::ADMIN_ROUTE))->assertDontSee('data-analytics-off', false);
    }

    #[Test]
    public function the_range_custom_dates_and_section_filter_are_validated_and_applied(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get(route(Analytics::ADMIN_ROUTE, ['range' => 'today']))->assertOk()->assertSee('24 October 2026, compared with yesterday.');
        $this->actingAs($editor)->get(route(Analytics::ADMIN_ROUTE, ['range' => '7d']))->assertOk()->assertSee('18 October 2026 – 24 October 2026, compared with the previous 7 days.');
        $this->actingAs($editor)->get(route(Analytics::ADMIN_ROUTE, ['range' => 'custom', 'from' => '2026-10-20', 'to' => '2026-10-23']))->assertOk()->assertSee('20 October 2026 – 23 October 2026')->assertSee('42');
        $this->actingAs($editor)->get(route(Analytics::ADMIN_ROUTE, ['range' => '12m']))->assertOk()->assertSee('Oct 2025');

        $this->actingAs($editor)->from(route(Analytics::ADMIN_ROUTE))->get(route(Analytics::ADMIN_ROUTE, ['range' => 'forever']))->assertRedirect()->assertSessionHasErrors('range');
        $this->actingAs($editor)->from(route(Analytics::ADMIN_ROUTE))->get(route(Analytics::ADMIN_ROUTE, ['range' => 'custom']))->assertSessionHasErrors(['from', 'to']);
        $this->actingAs($editor)->from(route(Analytics::ADMIN_ROUTE))->get(route(Analytics::ADMIN_ROUTE, ['range' => 'custom', 'from' => '2026-10-01', 'to' => '2027-01-01']))->assertSessionHasErrors('to');
        $this->actingAs($editor)->from(route(Analytics::ADMIN_ROUTE))->get(route(Analytics::ADMIN_ROUTE, ['section' => 'nope']))->assertSessionHasErrors('section');

        $this->actingAs($editor)->get(route(Analytics::ADMIN_ROUTE, ['section' => 'publications']))->assertOk()
            ->assertSee('data-page="/publications"', false)
            ->assertDontSee('data-page="/commentary/on-soil"', false);
        $this->actingAs($editor)->get(route(Analytics::ADMIN_ROUTE, ['section' => 'awards']))->assertOk()->assertSee('No pages in this section were read in this period.');
    }

    #[Test]
    public function every_table_downloads_as_csv(): void
    {
        $editor = User::factory()->editor()->create();

        foreach (Analytics::exports() as $export) {
            $response = $this->actingAs($editor)->get(route(Analytics::EXPORT_ROUTE, ['export' => $export, 'range' => '7d']))->assertOk();
            $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $this->assertStringContainsString('analytics-'.$export.'-2026-10-18-to-2026-10-24.csv', (string) $response->headers->get('Content-Disposition'));
            $this->assertNotSame('', trim($response->streamedContent()));
        }

        $pages = $this->actingAs($editor)->get(route(Analytics::EXPORT_ROUTE, ['export' => Analytics::EXPORT_PAGES, 'section' => 'commentary']))->streamedContent();
        $this->assertStringContainsString('"On soil and patience",/commentary/on-soil,Commentary,5400', $pages);
        $this->assertStringNotContainsString('/publications', $pages);

        $this->actingAs($editor)->get('/admin/analytics/export/secrets')->assertNotFound();
    }
}
