<?php

namespace Tests\Unit\Services;

use App\Constants\Brand;
use App\Constants\Theme;
use App\Constants\ThemeSettings;
use App\Models\User;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThemeServiceTest extends TestCase
{
    use RefreshDatabase;

    private ThemeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(ThemeService::class);
    }

    private function request(?User $user = null, ?string $cookie = null): Request
    {
        $request = Request::create('/', 'GET', cookies: $cookie === null ? [] : [ThemeSettings::COOKIE => $cookie]);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    #[Test]
    public function current_prefers_the_account_then_the_cookie_then_system(): void
    {
        $this->assertSame(Theme::System, $this->service->current($this->request()));
        $this->assertSame(Theme::Dark, $this->service->current($this->request(cookie: 'dark')));
        $this->assertSame(Theme::System, $this->service->current($this->request(cookie: 'nonsense')));

        $user = User::factory()->theme(Theme::Light)->create();
        $this->assertSame(Theme::Light, $this->service->current($this->request($user, 'dark')));
    }

    #[Test]
    public function remember_stores_on_the_user_and_returns_a_readable_cookie(): void
    {
        $user = User::factory()->create();

        $cookie = $this->service->remember(Theme::Dark, $user);

        $this->assertSame(Theme::Dark, $user->fresh()->theme);
        $this->assertSame(ThemeSettings::COOKIE, $cookie->getName());
        $this->assertSame('dark', $cookie->getValue());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertGreaterThan(time() + 86400 * 300, $cookie->getExpiresTime());

        $guestCookie = $this->service->remember(Theme::Light);
        $this->assertSame('light', $guestCookie->getValue());
    }

    #[Test]
    public function html_attributes_force_a_scheme_only_for_light_and_dark(): void
    {
        $this->assertSame('data-theme-preference="system"', (string) $this->service->htmlAttributes(Theme::System));
        $this->assertSame('data-theme-preference="dark" data-theme="dark"', (string) $this->service->htmlAttributes(Theme::Dark));
        $this->assertSame('data-theme-preference="light" data-theme="light"', (string) $this->service->htmlAttributes(Theme::Light));
    }

    #[Test]
    public function theme_colours_resolve_brand_names_and_fall_back_to_evergreen(): void
    {
        $this->assertSame(['light' => Brand::EVERGREEN, 'dark' => Brand::CYPRESS], $this->service->themeColours('evergreen', 'cypress'));
        $this->assertSame(['light' => Brand::IVORY, 'dark' => Brand::EVERGREEN], $this->service->themeColours('ivory', 'not-a-colour'));
    }

    #[Test]
    public function options_list_the_three_themes_in_order_with_the_current_one_marked(): void
    {
        $options = $this->service->options(Theme::Dark);

        $this->assertSame(['system', 'light', 'dark'], array_column($options, 'value'));
        $this->assertSame([false, false, true], array_column($options, 'current'));
        $this->assertSame('theme-dark', $options[2]['icon']);
        $this->assertSame(Theme::Dark->label(), $options[2]['label']);
    }
}
