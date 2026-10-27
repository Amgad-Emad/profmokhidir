<?php

namespace Tests\Unit\Services;

use App\Constants\Locales;
use App\Constants\SettingKeys;
use App\Models\User;
use App\Services\LocaleService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private LocaleService $locales;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locales = $this->app->make(LocaleService::class);
    }

    #[Test]
    public function arabic_is_preferred_only_when_the_browser_ranks_it_above_english(): void
    {
        $this->assertTrue($this->locales->prefersArabic('ar-SD,ar;q=0.9,en;q=0.8'));
        $this->assertTrue($this->locales->prefersArabic('fr-FR, ar;q=0.8, en;q=0.5'));
        $this->assertTrue($this->locales->prefersArabic('en;q=0.4, ar;q=0.6'));
        $this->assertFalse($this->locales->prefersArabic('en-GB,en;q=0.9,ar;q=0.8'));
        $this->assertFalse($this->locales->prefersArabic('ar;q=0, en'));
        $this->assertFalse($this->locales->prefersArabic(''));
    }

    #[Test]
    public function the_saved_choice_is_the_users_then_the_cookies_then_english(): void
    {
        $this->assertSame(Locales::EN, $this->locales->preferred(Request::create('/login')));
        $this->assertSame(Locales::AR, $this->locales->preferred(Request::create('/login', cookies: [Locales::COOKIE => 'ar'])));

        $request = Request::create('/login', cookies: [Locales::COOKIE => 'ar']);
        $request->setUserResolver(fn () => User::factory()->locale(Locales::EN)->make());
        $this->assertSame(Locales::EN, $this->locales->preferred($request));
    }

    #[Test]
    public function remembering_a_choice_saves_it_on_the_user_and_returns_a_plain_cookie(): void
    {
        $user = User::factory()->create();
        $cookie = $this->locales->remember(Locales::AR, $user);

        $this->assertSame(Locales::COOKIE, $cookie->getName());
        $this->assertSame(Locales::AR, $cookie->getValue());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertSame(Locales::AR, $user->fresh()->locale);
    }

    #[Test]
    public function only_paths_on_this_site_are_safe_targets(): void
    {
        $this->assertSame('/ar/about?x=1', $this->locales->safeTarget('/ar/about?x=1', Locales::AR));
        $this->assertSame('/ar', $this->locales->safeTarget('https://evil.example', Locales::AR));
        $this->assertSame('/ar', $this->locales->safeTarget('//evil.example', Locales::AR));
        $this->assertSame('/', $this->locales->safeTarget(null, Locales::EN));
    }

    #[Test]
    public function digits_stay_western_unless_arabic_indic_is_chosen_and_the_page_is_arabic(): void
    {
        app()->setLocale(Locales::AR);
        $this->assertSame('12 منشورًا', $this->locales->digits('12 منشورًا'));

        $this->app->make(SettingService::class)->update([SettingKeys::ARABIC_DIGITS => Locales::DIGITS_ARABIC]);
        $this->assertSame('١٢ منشورًا', $this->locales->digits('12 منشورًا'));

        app()->setLocale(Locales::EN);
        $this->assertSame('12', $this->locales->digits(12));
    }

    #[Test]
    public function dates_are_gregorian_with_arabic_month_names_and_western_digits(): void
    {
        $this->locales->apply(Locales::AR);
        $this->assertSame('12 مارس 2024', $this->locales->date(Carbon::create(2024, 3, 12)));

        $this->locales->apply(Locales::EN);
        $this->assertSame('12 March 2024', $this->locales->date(Carbon::create(2024, 3, 12)));
    }
}
