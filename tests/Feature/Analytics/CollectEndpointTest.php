<?php

namespace Tests\Feature\Analytics;

use App\Constants\Analytics;
use App\Constants\RateLimits;
use App\Constants\SettingKeys;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSalt;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Unit\Support\AnalyticsSupportTest;

class CollectEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const string IP = '203.0.113.77';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(SettingService::class)->update([SettingKeys::ANALYTICS_ENABLED => true]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    private function beacon(array $payload = [], array $headers = [], string $userAgent = AnalyticsSupportTest::CHROME_DESKTOP): TestResponse
    {
        return $this->flushHeaders()->withServerVariables(['REMOTE_ADDR' => self::IP])
            ->withHeaders(['User-Agent' => $userAgent, ...$headers])
            ->postJson(Analytics::COLLECT_PATH, ['t' => 'pageview', 'v' => (string) Str::uuid(), 'sw' => 1440, 'p' => '/about', 'ti' => 'About Prof. Mokhidir · Professor Mohamed Osman Khidir', 'r' => 'https://scholar.google.com/x', ...$payload]);
    }

    #[Test]
    public function a_page_view_is_recorded_with_an_empty_204_and_no_ip_or_user_agent_is_stored(): void
    {
        $this->beacon()->assertNoContent()->assertHeader('Cache-Control', 'no-store, private');

        $event = AnalyticsEvent::query()->sole();
        $this->assertSame('/about', $event->path);
        $this->assertSame('scholar.google.com', $event->referrer_domain);
        $this->assertSame('Chrome', $event->browser);

        // Search every analytics table for the address and the user agent, whole or in part.
        foreach (['analytics_events', 'analytics_salts', 'analytics_daily_totals', 'analytics_daily_pages', 'analytics_daily_countries', 'analytics_daily_referrers', 'analytics_daily_technologies'] as $table) {
            $dump = json_encode(DB::table($table)->get());
            $this->assertStringNotContainsString(self::IP, $dump, $table);
            $this->assertStringNotContainsString('203.0.113', $dump, $table);
            $this->assertStringNotContainsString('Macintosh', $dump, $table);
            $this->assertStringNotContainsString('AppleWebKit', $dump, $table);
        }

        $this->assertNotContains('ip', array_keys($event->getAttributes()));
        $this->assertNotContains('user_agent', array_keys($event->getAttributes()));
        $this->assertSame(1, AnalyticsSalt::query()->count());
    }

    #[Test]
    public function the_engagement_beacon_raises_engaged_time_and_scroll_depth(): void
    {
        $view = (string) Str::uuid();
        $this->beacon(['v' => $view]);

        $this->beacon(['t' => 'engagement', 'v' => $view, 'e' => 42, 's' => 50])->assertNoContent();
        $this->beacon(['t' => 'engagement', 'v' => $view, 'e' => 61, 's' => 75])->assertNoContent();

        $event = AnalyticsEvent::query()->sole();
        $this->assertSame(61, $event->engaged_seconds);
        $this->assertSame(75, $event->scroll_depth);
    }

    #[Test]
    public function the_beacon_body_may_arrive_as_text_plain_like_send_beacon_sends_it(): void
    {
        $body = json_encode(['t' => 'pageview', 'v' => (string) Str::uuid(), 'sw' => 390, 'p' => '/publications']);

        $this->withServerVariables(['REMOTE_ADDR' => self::IP])
            ->call('POST', Analytics::COLLECT_PATH, [], [], [], ['CONTENT_TYPE' => 'text/plain;charset=UTF-8', 'HTTP_USER_AGENT' => AnalyticsSupportTest::IPHONE], $body)
            ->assertNoContent();

        $this->assertSame('/publications', AnalyticsEvent::query()->sole()->path);
    }

    #[Test]
    public function bots_are_ignored(): void
    {
        foreach (['Mozilla/5.0 (compatible; Googlebot/2.1)', 'Mozilla/5.0 (Linux) Chrome/130 Safari/537.36 Chrome-Lighthouse', 'curl/8.4.0', ''] as $agent) {
            $this->beacon(userAgent: $agent)->assertNoContent();
        }

        $this->assertSame(0, AnalyticsEvent::query()->count());
    }

    #[Test]
    public function beacons_without_the_javascript_signals_or_with_bad_fields_are_refused(): void
    {
        $this->beacon(['sw' => null])->assertStatus(422)->assertContent('');
        $this->beacon(['v' => 'not-a-uuid'])->assertStatus(422);
        $this->beacon(['t' => 'click'])->assertStatus(422);
        $this->beacon(['p' => 'https://evil.example/'])->assertStatus(422);
        $this->beacon(['p' => null])->assertStatus(422);
        $this->beacon(['t' => 'engagement', 'e' => 10, 's' => 33])->assertStatus(422);
        $this->beacon(['t' => 'engagement', 's' => 25])->assertStatus(422);

        $this->assertSame(0, AnalyticsEvent::query()->count());
    }

    #[Test]
    public function signed_in_staff_are_ignored_but_members_are_counted(): void
    {
        $this->actingAs(User::factory()->editor()->create());
        $this->beacon()->assertNoContent();
        $this->actingAs(User::factory()->admin()->create());
        $this->beacon()->assertNoContent();
        $this->assertSame(0, AnalyticsEvent::query()->count());

        $this->actingAs(User::factory()->member()->create());
        $this->beacon()->assertNoContent();
        $this->assertSame(1, AnalyticsEvent::query()->count());
    }

    #[Test]
    public function do_not_track_and_global_privacy_control_are_respected(): void
    {
        $this->beacon(headers: ['DNT' => '1'])->assertNoContent();
        $this->beacon(headers: ['Sec-GPC' => '1'])->assertNoContent();
        $this->assertSame(0, AnalyticsEvent::query()->count());

        $this->beacon(headers: ['DNT' => '0'])->assertNoContent();
        $this->assertSame(1, AnalyticsEvent::query()->count());
    }

    #[Test]
    public function nothing_is_recorded_while_analytics_is_off(): void
    {
        $this->app->make(SettingService::class)->update([SettingKeys::ANALYTICS_ENABLED => false]);

        $this->beacon()->assertNoContent();

        $this->assertSame(0, AnalyticsEvent::query()->count());
        $this->assertSame(0, AnalyticsSalt::query()->count());
    }

    #[Test]
    public function excluded_pages_are_never_recorded(): void
    {
        foreach (['/admin', '/admin/analytics', '/community/chat', '/login', '/account'] as $path) {
            $this->beacon(['p' => $path])->assertNoContent();
        }

        $this->assertSame(0, AnalyticsEvent::query()->count());
    }

    #[Test]
    public function the_endpoint_is_rate_limited_and_exempt_from_csrf(): void
    {
        $route = Route::getRoutes()->getByName(Analytics::COLLECT_ROUTE);
        $this->assertContains('throttle:'.RateLimits::ANALYTICS_COLLECT, $route->gatherMiddleware());
        $this->assertContains(ltrim(Analytics::COLLECT_PATH, '/'), (new \ReflectionClass(PreventRequestForgery::class))->getStaticPropertyValue('neverVerify'));

        for ($i = 0; $i < RateLimits::ANALYTICS_COLLECT_PER_MINUTE; $i++) {
            $this->beacon(headers: ['DNT' => '1'])->assertNoContent();
        }

        $this->beacon()->assertStatus(429);
        $this->assertSame(0, AnalyticsEvent::query()->count());
    }
}
