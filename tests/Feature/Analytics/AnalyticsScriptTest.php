<?php

namespace Tests\Feature\Analytics;

use App\Constants\Analytics;
use App\Constants\SettingKeys;
use App\Models\ChatChannel;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsScriptTest extends TestCase
{
    use RefreshDatabase;

    private const string MARKER = '<meta name="analytics"';

    private function enable(): void
    {
        $this->app->make(SettingService::class)->update([SettingKeys::ANALYTICS_ENABLED => true]);
    }

    #[Test]
    public function public_pages_carry_the_script_only_while_analytics_is_on(): void
    {
        foreach (['/', '/about', '/publications', '/tributes'] as $path) {
            $this->get($path)->assertOk()->assertDontSee(self::MARKER, false);
        }

        $this->enable();

        foreach (['/', '/about', '/publications', '/tributes'] as $path) {
            $this->get($path)->assertOk()->assertSee('<meta name="analytics" content="/a/collect">', false);
        }
    }

    #[Test]
    public function the_script_never_reaches_the_dashboard_the_chat_or_the_sign_in_screens(): void
    {
        $this->enable();
        ChatChannel::factory()->general()->create();

        foreach (['/login', '/forgot-password', '/register'] as $path) {
            $response = $this->get($path);

            if ($response->getStatusCode() === 200) {
                $response->assertDontSee(self::MARKER, false);
            }
        }

        $this->get('/login')->assertOk()->assertDontSee(self::MARKER, false);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertDontSee(self::MARKER, false);
        $this->actingAs($admin)->get(route(Analytics::ADMIN_ROUTE))->assertOk()->assertDontSee(self::MARKER, false);
        $this->actingAs($admin)->get('/account')->assertOk()->assertDontSee(self::MARKER, false);

        $member = User::factory()->member()->create();
        $this->actingAs($member)->get(route('community.chat'))->assertOk()->assertDontSee(self::MARKER, false);
    }

    #[Test]
    public function signed_in_staff_and_browsers_asking_not_to_be_tracked_get_no_script(): void
    {
        $this->enable();

        $this->actingAs(User::factory()->editor()->create())->get('/about')->assertOk()->assertDontSee(self::MARKER, false);
        $this->actingAs(User::factory()->member()->create())->get('/about')->assertOk()->assertSee(self::MARKER, false);

        $this->withHeaders(['DNT' => '1'])->get('/about')->assertOk()->assertDontSee(self::MARKER, false);
        $this->withHeaders(['Sec-GPC' => '1'])->get('/about')->assertOk()->assertDontSee(self::MARKER, false);
    }

    #[Test]
    public function the_script_sets_no_cookie_and_stores_nothing_in_the_browser(): void
    {
        $script = (string) file_get_contents(resource_path('js/analytics.js'));

        $this->assertStringNotContainsString('document.cookie', $script);
        $this->assertStringNotContainsString('localStorage', $script);
        $this->assertStringNotContainsString('sessionStorage', $script);
        $this->assertStringNotContainsString('indexedDB', $script);
        $this->assertStringContainsString('navigator.sendBeacon', $script);
        $this->assertStringContainsString('globalPrivacyControl', $script);
        $this->assertStringContainsString('doNotTrack', $script);
        $this->assertStringContainsString("'visibilitychange'", $script);
        $this->assertStringContainsString("'pagehide'", $script);

        // The script's own copies of the idle window and scroll marks match the constants.
        $this->assertStringContainsString('const IDLE_SECONDS = '.Analytics::IDLE_SECONDS.';', $script);
        $this->assertStringContainsString('const SCROLL_DEPTHS = ['.implode(', ', Analytics::SCROLL_DEPTHS).'];', $script);

        // app.js imports it only where the meta tag is present, after load and first paint.
        $app = (string) file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString('document.querySelector(\'meta[name="analytics"]\')', $app);
        $this->assertStringContainsString("import('./analytics')", $app);
    }
}
