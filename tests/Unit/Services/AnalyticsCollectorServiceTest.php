<?php

namespace Tests\Unit\Services;

use App\Constants\Analytics;
use App\Constants\DeviceType;
use App\Constants\SettingKeys;
use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Services\AnalyticsCollectorService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Unit\Support\AnalyticsSupportTest;

class AnalyticsCollectorServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsCollectorService $collector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collector = $this->app->make(AnalyticsCollectorService::class);
        $this->app->make(SettingService::class)->update([SettingKeys::ANALYTICS_ENABLED => true]);
    }

    private function request(array $server = []): Request
    {
        return Request::create('/a/collect', 'POST', server: ['REMOTE_ADDR' => '198.51.100.7', 'HTTP_USER_AGENT' => AnalyticsSupportTest::IPHONE, 'HTTP_HOST' => 'profmokhidir.test', ...$server]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageview(array $overrides = []): array
    {
        return ['t' => 'pageview', 'v' => (string) Str::uuid(), 'sw' => 390, 'p' => '/Commentary/On-Soil/', 'ti' => 'On soil · Professor Mohamed Osman Khidir', 'r' => 'https://www.google.com/search?q=x', 'u' => 'News Letter!', ...$overrides];
    }

    #[Test]
    public function a_page_view_is_stored_with_derived_fields_only(): void
    {
        $this->assertNull($this->collector->collect($this->request(), $payload = $this->pageview()));

        $event = AnalyticsEvent::query()->sole();
        $this->assertSame($payload['v'], $event->view_id);
        $this->assertSame('/commentary/on-soil', $event->path);
        $this->assertSame('On soil', $event->title);
        $this->assertSame('commentary', $event->section);
        $this->assertSame('google.com', $event->referrer_domain);
        $this->assertSame(Analytics::UNKNOWN_COUNTRY, $event->country);
        $this->assertSame(DeviceType::Mobile, $event->device);
        $this->assertSame('Safari', $event->browser);
        $this->assertSame('news-letter', $event->utm_source);
        $this->assertSame(0, $event->engaged_seconds);
        $this->assertStringNotContainsString('198.51.100.7', json_encode($event->getAttributes()));
    }

    #[Test]
    public function engagement_updates_the_view_and_is_capped(): void
    {
        $this->collector->collect($this->request(), $payload = $this->pageview());

        $this->assertNull($this->collector->collect($this->request(), ['t' => 'engagement', 'v' => $payload['v'], 'sw' => 390, 'e' => 99999, 's' => 75]));
        $this->assertSame(AnalyticsCollectorService::IGNORED_UNKNOWN_VIEW, $this->collector->collect($this->request(['REMOTE_ADDR' => '198.51.100.8']), ['t' => 'engagement', 'v' => $payload['v'], 'sw' => 390, 'e' => 5, 's' => 100]));

        $event = AnalyticsEvent::query()->sole();
        $this->assertSame(Analytics::MAX_ENGAGED_SECONDS, $event->engaged_seconds);
        $this->assertSame(75, $event->scroll_depth);
    }

    #[Test]
    public function ignored_requests_say_why_and_store_nothing(): void
    {
        $staff = $this->request();
        $staff->setUserResolver(fn () => User::factory()->admin()->create());

        $this->assertSame(AnalyticsCollectorService::IGNORED_STAFF, $this->collector->collect($staff, $this->pageview()));
        $this->assertSame(AnalyticsCollectorService::IGNORED_BOT, $this->collector->collect($this->request(['HTTP_USER_AGENT' => 'Googlebot/2.1']), $this->pageview()));
        $this->assertSame(AnalyticsCollectorService::IGNORED_OPTED_OUT, $this->collector->collect($this->request(['HTTP_DNT' => '1']), $this->pageview()));
        $this->assertSame(AnalyticsCollectorService::IGNORED_OPTED_OUT, $this->collector->collect($this->request(['HTTP_SEC_GPC' => '1']), $this->pageview()));
        $this->assertSame(AnalyticsCollectorService::IGNORED_PATH, $this->collector->collect($this->request(), $this->pageview(['p' => '/admin/analytics'])));

        $payload = $this->pageview();
        $this->collector->collect($this->request(), $payload);
        $this->assertSame(AnalyticsCollectorService::IGNORED_DUPLICATE, $this->collector->collect($this->request(), $payload));
        $this->assertSame(1, AnalyticsEvent::query()->count());

        $this->app->make(SettingService::class)->update([SettingKeys::ANALYTICS_ENABLED => false]);
        $this->assertSame(AnalyticsCollectorService::IGNORED_DISABLED, $this->collector->collect($this->request(), $this->pageview()));
        $this->assertSame(1, AnalyticsEvent::query()->count());
    }

    #[Test]
    public function titles_and_utm_sources_are_cleaned(): void
    {
        $this->assertSame('Home', AnalyticsCollectorService::title('Professor Mohamed Osman Khidir'));
        $this->assertSame('Publications', AnalyticsCollectorService::title('<b>Publications</b> · Professor Mohamed Osman Khidir'));
        $this->assertNull(AnalyticsCollectorService::title('   '));
        $this->assertSame(AnalyticsCollectorService::TITLE_MAX, mb_strlen((string) AnalyticsCollectorService::title(str_repeat('a', 400))));
        $this->assertNull(AnalyticsCollectorService::utmSource('!!!'));
        $this->assertSame('/', AnalyticsCollectorService::path('/'));
        $this->assertSame('/about', AnalyticsCollectorService::path('/about/'));
    }
}
