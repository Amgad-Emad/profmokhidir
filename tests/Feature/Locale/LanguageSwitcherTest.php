<?php

namespace Tests\Feature\Locale;

use App\Constants\Locales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LanguageSwitcherTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_switcher_links_to_the_same_page_in_the_other_language_keeping_the_query(): void
    {
        $html = $this->get('/publications?type=book&year=1978')->getContent();
        $expected = e(route(Locales::SWITCH_ROUTE, ['locale' => 'ar', 'to' => '/ar/publications?type=book&year=1978']));
        $this->assertStringContainsString('href="'.$expected.'"', $html);
        $this->assertStringContainsString('العربية', $html);

        $arabic = $this->get('/ar/publications?type=book')->getContent();
        $this->assertStringContainsString(e(route(Locales::SWITCH_ROUTE, ['locale' => 'en', 'to' => '/publications?type=book'])), $arabic);
        $this->assertStringContainsString('English', $arabic);
    }

    #[Test]
    public function the_switcher_appears_in_the_header_the_mobile_menu_and_the_footer(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('locale-switch--masthead site-header__locale', $html);
        $this->assertStringContainsString('site-menu__locale', $html);
        $this->assertStringContainsString('locale-switch--footer', $html);
    }

    #[Test]
    public function switching_stores_the_cookie_and_opens_the_target(): void
    {
        $this->get('/language/ar?to='.urlencode('/ar/about'))
            ->assertRedirect('/ar/about')
            ->assertPlainCookie(Locales::COOKIE, Locales::AR);
    }

    #[Test]
    public function a_signed_in_user_keeps_the_choice_on_the_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/language/ar?to=/account')->assertRedirect('/account');

        $this->assertSame(Locales::AR, $user->fresh()->locale);
        $this->assertSame(Locales::AR, $user->fresh()->preferredLocale());
    }

    #[Test]
    public function the_target_must_be_a_path_on_this_site(): void
    {
        foreach (['https://evil.example/', '//evil.example/x', '/\\evil.example', 'javascript:alert(1)'] as $target) {
            $this->get('/language/ar?to='.urlencode($target))->assertRedirect(url('/ar'));
        }

        $this->get('/language/xx?to=/')->assertNotFound();
    }

    #[Test]
    public function pages_without_an_arabic_address_switch_in_place(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/account')->getContent();

        $this->assertStringContainsString(e(route(Locales::SWITCH_ROUTE, ['locale' => 'ar', 'to' => '/account'])), $html);
    }
}
