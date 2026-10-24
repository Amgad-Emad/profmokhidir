<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\Analytics;
use App\Constants\SettingKeys;
use App\Models\AnalyticsDailyCountry;
use App\Models\AnalyticsDailyPage;
use App\Models\AnalyticsDailyTotal;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardAudienceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_dashboard_says_analytics_is_off_and_links_to_the_report(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route(AdminNavigation::DASHBOARD_ROUTE))->assertOk()
            ->assertSee('data-section="audience"', false)
            ->assertSee('Visitor analytics is switched off')
            ->assertSee(route(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS)).'#analytics', false)
            ->assertSee('href="'.route(Analytics::ADMIN_ROUTE).'"', false)
            ->assertDontSee('data-figure="visitors"', false);
    }

    #[Test]
    public function with_figures_the_section_shows_the_headline_top_pages_and_countries(): void
    {
        $this->app->make(SettingService::class)->update([SettingKeys::ANALYTICS_ENABLED => true]);
        AnalyticsDailyTotal::factory()->forDay(now()->subDays(3))->create(['visitors' => 77]);
        AnalyticsDailyPage::factory()->forDay(now()->subDays(3))->page('/about', 'About Prof. Mokhidir')->create(['engaged_seconds' => 900]);
        AnalyticsDailyCountry::factory()->forDay(now()->subDays(3))->country('EG')->create();

        $this->actingAs(User::factory()->editor()->create())->get(route(AdminNavigation::DASHBOARD_ROUTE))->assertOk()
            ->assertDontSee('data-analytics-off', false)
            ->assertSee('data-figure="visitors"', false)
            ->assertSee('77')
            ->assertSee('About Prof. Mokhidir')
            ->assertSee('Egypt')
            ->assertSee('Open the full report');
    }

    #[Test]
    public function users_without_view_analytics_see_no_audience_section(): void
    {
        $this->assertNull($this->app->make(DashboardService::class)->summary(User::factory()->member()->create())['audience']);
    }
}
