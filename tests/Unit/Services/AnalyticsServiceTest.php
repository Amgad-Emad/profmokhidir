<?php

namespace Tests\Unit\Services;

use App\Constants\Analytics;
use App\Constants\SettingKeys;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(AnalyticsService::class);
    }

    private function enable(): void
    {
        $this->app->make(SettingService::class)->update([SettingKeys::ANALYTICS_ENABLED => true]);
    }

    #[Test]
    public function analytics_is_off_by_default_and_follows_the_setting(): void
    {
        $this->assertFalse($this->service->enabled());
        $this->assertFalse($this->service->shouldLoadScript(Request::create('/about')));

        $this->enable();

        $this->assertTrue($this->service->enabled());
        $this->assertTrue($this->service->shouldLoadScript(Request::create('/about')));
    }

    #[Test]
    public function staff_privacy_signals_and_excluded_paths_never_get_the_script(): void
    {
        $this->enable();

        $staff = Request::create('/about');
        $staff->setUserResolver(fn () => User::factory()->editor()->create());
        $member = Request::create('/about');
        $member->setUserResolver(fn () => User::factory()->member()->create());

        $this->assertFalse($this->service->shouldLoadScript($staff));
        $this->assertTrue($this->service->shouldLoadScript($member));
        $this->assertFalse($this->service->shouldLoadScript(Request::create('/about', server: ['HTTP_DNT' => '1'])));
        $this->assertFalse($this->service->shouldLoadScript(Request::create('/about', server: ['HTTP_SEC_GPC' => '1'])));
        $this->assertTrue($this->service->shouldLoadScript(Request::create('/about', server: ['HTTP_DNT' => '0'])));
        $this->assertFalse($this->service->shouldLoadScript(Request::create('/login')));
        $this->assertFalse($this->service->shouldLoadScript(Request::create('/community/chat')));
    }

    #[Test]
    public function the_meta_tag_carries_only_the_collectors_path(): void
    {
        $this->assertSame(Analytics::COLLECT_PATH, $this->service->endpoint());
    }
}
