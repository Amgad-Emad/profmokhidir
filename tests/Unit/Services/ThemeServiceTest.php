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
    public function current_prefers_the_account_then_the_cookie_then_nothing(): void
    {
        $this->assertNull($this->service->current($this->request()));
        $this->assertSame(Theme::Dark, $this->service->current($this->request(cookie: 'dark')));
        $this->assertNull($this->service->current($this->request(cookie: 'nonsense')));
        $this->assertNull($this->service->current($this->request(cookie: ThemeSettings::LEGACY_SYSTEM)), 'the retired value is resolved on the client');

        $user = User::factory()->lightTheme()->create();
        $this->assertSame(Theme::Light, $this->service->current($this->request($user, 'dark')));

        $undecided = User::factory()->create();
        $this->assertSame(Theme::Dark, $this->service->current($this->request($undecided, 'dark')), 'an account without a choice follows the cookie');
        $this->assertNull($this->service->current($this->request($undecided)));
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
    public function html_attributes_force_a_scheme_only_once_one_is_chosen(): void
    {
        $this->assertSame('', (string) $this->service->htmlAttributes(null));
        $this->assertSame('data-theme="dark"', (string) $this->service->htmlAttributes(Theme::Dark));
        $this->assertSame('data-theme="light"', (string) $this->service->htmlAttributes(Theme::Light));
    }

    #[Test]
    public function theme_colours_resolve_brand_names_and_fall_back_to_evergreen(): void
    {
        $this->assertSame(['light' => Brand::EVERGREEN, 'dark' => Brand::CYPRESS], $this->service->themeColours('evergreen', 'cypress'));
        $this->assertSame(['light' => Brand::IVORY, 'dark' => Brand::EVERGREEN], $this->service->themeColours('ivory', 'not-a-colour'));
    }

    #[Test]
    public function the_toggle_names_the_other_theme_and_is_pressed_in_the_dark(): void
    {
        $this->assertSame(
            ['theme' => 'dark', 'next' => 'light', 'label' => 'Switch to light theme', 'pressed' => 'true', 'chosen' => true],
            $this->service->toggle(Theme::Dark),
        );
        $this->assertSame(
            ['theme' => 'light', 'next' => 'dark', 'label' => 'Switch to dark theme', 'pressed' => 'false', 'chosen' => true],
            $this->service->toggle(Theme::Light),
        );
    }

    #[Test]
    public function with_nothing_chosen_the_toggle_assumes_light_until_the_script_reads_the_device(): void
    {
        $toggle = $this->service->toggle(null);

        $this->assertSame('light', $toggle['theme']);
        $this->assertSame('dark', $toggle['next']);
        $this->assertSame('Switch to dark theme', $toggle['label']);
        $this->assertFalse($toggle['chosen']);
    }
}
