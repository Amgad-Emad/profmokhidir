<?php

namespace Tests\Feature\Locale;

use App\Constants\Locales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocaleRoutingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function publicPaths(): array
    {
        return array_map(fn (string $path): array => [$path], [
            'home' => '', 'about' => '/about', 'cv' => '/cv', 'publications' => '/publications', 'awards' => '/awards',
            'videos' => '/videos', 'pictures' => '/pictures', 'teaching' => '/teaching', 'commentary' => '/commentary',
            'community' => '/community', 'other' => '/other', 'tributes' => '/tributes', 'tribute form' => '/tributes/new', 'search' => '/search',
        ]);
    }

    #[Test]
    #[DataProvider('publicPaths')]
    public function every_public_page_is_served_in_english_and_under_ar_in_arabic(string $path): void
    {
        $this->get($path === '' ? '/' : $path)->assertOk()->assertSee('<html lang="en" dir="ltr"', false);
        $this->get('/ar'.$path)->assertOk()->assertSee('<html lang="ar" dir="rtl"', false);
    }

    #[Test]
    public function links_on_an_arabic_page_stay_in_arabic_and_english_links_stay_plain(): void
    {
        $arabic = $this->get('/ar/publications')->getContent();
        $this->assertStringContainsString('href="'.url('/ar/about').'"', $arabic);
        $this->assertStringNotContainsString('href="'.url('/about').'"', $arabic);

        $english = $this->get('/publications')->getContent();
        $this->assertStringContainsString('href="'.url('/about').'"', $english);
        $this->assertStringNotContainsString(url('/ar/about'), str_replace('hreflang="ar" href="'.url('/ar/publications').'"', '', $english));
    }

    #[Test]
    public function the_route_generator_follows_the_current_language_only_for_pages_with_an_arabic_twin(): void
    {
        app()->setLocale(Locales::AR);

        $this->assertSame(url('/ar/publications'), route('publications'));
        $this->assertSame(url('/ar/commentary/a-slug'), route('commentary.show', 'a-slug'));
        $this->assertSame(url('/login'), route('login'));
        $this->assertSame(url('/publications'), app('url')->routeIn(Locales::EN, 'publications'));

        app()->setLocale(Locales::EN);
        $this->assertSame(url('/publications'), route('publications'));
    }

    #[Test]
    public function an_unknown_address_under_ar_gets_the_arabic_not_found_page(): void
    {
        $this->get('/ar/no-such-page')->assertNotFound()->assertSee('<html lang="ar" dir="rtl"', false);
        $this->get('/no-such-page')->assertNotFound()->assertSee('<html lang="en" dir="ltr"', false);
    }

    #[Test]
    public function public_pages_ignore_the_saved_choice_because_the_address_decides(): void
    {
        $this->withUnencryptedCookie(Locales::COOKIE, Locales::AR)->get('/about')
            ->assertSee('<html lang="en" dir="ltr"', false);

        $this->actingAs(User::factory()->arabic()->create())->get('/about')
            ->assertSee('<html lang="en" dir="ltr"', false);
    }

    #[Test]
    public function sign_in_follows_the_cookie_and_the_account_follows_the_user(): void
    {
        $this->get('/login')->assertSee('<html lang="en" dir="ltr"', false);
        $this->withUnencryptedCookie(Locales::COOKIE, Locales::AR)->get('/login')->assertSee('<html lang="ar" dir="rtl"', false);
        $this->withUnencryptedCookie(Locales::COOKIE, 'xx')->get('/login')->assertSee('<html lang="en" dir="ltr"', false);

        $this->actingAs(User::factory()->arabic()->create())->get('/account')->assertOk()->assertSee('<html lang="ar" dir="rtl"', false);
    }

    #[Test]
    public function the_arabic_suggestion_shows_only_to_arabic_preferring_browsers_on_english_pages(): void
    {
        $this->get('/', ['Accept-Language' => 'ar-SD,ar;q=0.9,en;q=0.8'])->assertSee('data-locale-suggestion', false)->assertSee('اقرأ الموقع بالعربية');
        $this->get('/', ['Accept-Language' => 'en-GB,en;q=0.9,ar;q=0.5'])->assertDontSee('data-locale-suggestion', false);
        $this->get('/ar', ['Accept-Language' => 'ar'])->assertDontSee('data-locale-suggestion', false);
        $this->withUnencryptedCookie(Locales::SUGGESTION_COOKIE, '1')->get('/', ['Accept-Language' => 'ar'])->assertDontSee('data-locale-suggestion', false);
        $this->withUnencryptedCookie(Locales::COOKIE, Locales::EN)->get('/', ['Accept-Language' => 'ar'])->assertDontSee('data-locale-suggestion', false);
    }

    #[Test]
    public function there_is_never_a_forced_redirect_by_browser_language(): void
    {
        $this->get('/publications', ['Accept-Language' => 'ar'])->assertOk();
    }
}
