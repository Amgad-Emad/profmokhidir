<?php

namespace Tests\Feature\Pages;

use App\Constants\Analytics;
use App\Constants\SettingKeys;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PrivacyPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_privacy_page_and_its_footer_link_exist_only_while_analytics_is_on(): void
    {
        $this->get(Analytics::PRIVACY_PATH)->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('href="/privacy"', false);

        $this->app->make(SettingService::class)->update([SettingKeys::ANALYTICS_ENABLED => true]);

        $this->get('/about')->assertOk()->assertSee('<a href="/privacy" class="footer-link">Privacy</a>', false);
        $this->get(Analytics::PRIVACY_PATH)->assertOk()
            ->assertSee('<title>Privacy · Professor Mohamed Osman Khidir</title>', false)
            ->assertSee('How this site counts its readers')
            ->assertSee('No cookies are set')
            ->assertSee('Global Privacy Control')
            ->assertSee('deleted after 30 days')
            ->assertSee('deleted after 25 months')
            ->assertSee('country')
            ->assertSee('aria-current="page"', false);
    }
}
