<?php

namespace Tests\Feature\Locale;

use App\Constants\AdminNavigation;
use App\Constants\Locales;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The sign-in screens and the account page have no address prefix: they follow the
 * user's saved language, else the `locale` cookie the switcher sets.
 */
class ArabicAuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_login_page_follows_the_locale_cookie(): void
    {
        $this->withUnencryptedCookie(Locales::COOKIE, Locales::AR)
            ->get(route('login'))
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('<title>تسجيل الدخول · ', false)
            ->assertSee('يسجّل أعضاء المجتمع العلمي وفريق التحرير دخولهم من هنا.')
            ->assertSee('البريد الإلكتروني')
            ->assertSee('هل نسيت كلمة المرور؟')
            ->assertSee('إظهار كلمة المرور')
            ->assertSee('العودة إلى الموقع')
            // Addresses and passwords stay left to right inside the right-to-left page.
            ->assertSee('type="email"', false)
            ->assertSee('dir="ltr"', false)
            // The form scripts' strings are printed for the page's language.
            ->assertSee('"عدد الخيارات: :count"', false)
            ->assertDontSee('Keep me signed in');
    }

    #[Test]
    public function the_login_page_stays_english_without_a_choice(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertSee('Keep me signed in')
            ->assertDontSee('window.i18n', false);
    }

    #[Test]
    public function a_failed_sign_in_reads_in_arabic(): void
    {
        $user = User::factory()->create();

        $this->withUnencryptedCookie(Locales::COOKIE, Locales::AR)
            ->from(route('login'))
            ->followingRedirects()
            ->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('بيانات الدخول هذه لا تطابق سجلاتنا.')
            ->assertSee('في هذا النموذج مشكلة');

        $this->assertGuest();
    }

    #[Test]
    public function a_validation_error_reads_in_arabic_with_the_field_name(): void
    {
        $this->withUnencryptedCookie(Locales::COOKIE, Locales::AR)
            ->from(route('password.request'))
            ->post(route('password.email'), ['email' => ''])
            ->assertSessionHasErrors(['email' => 'أدخل البريد الإلكتروني.']);
    }

    #[Test]
    public function the_two_factor_challenge_keeps_the_code_boxes_left_to_right(): void
    {
        $user = User::factory()->create(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()]);

        $this->withSession(['login.id' => $user->getKey()])
            ->withUnencryptedCookie(Locales::COOKIE, Locales::AR)
            ->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee('<div class="form-otp" dir="ltr"', false)
            ->assertSee('aria-label="الرقم 1 من 6"', false)
            ->assertSee('استخدم رمز استرداد بدلًا من ذلك');
    }

    #[Test]
    public function the_account_page_offers_the_language_and_follows_the_saved_one(): void
    {
        $user = User::factory()->arabic()->create();

        $response = $this->actingAs($user)->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('<h2 id="language-heading" class="font-display text-3xl font-light">اللغة</h2>', false)
            ->assertSee('data-locale-option="ar"', false)
            ->assertSee('data-locale-option="en"', false)
            ->assertSee('حفظ البيانات');

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/aria-current="true"[^>]*data-locale-option="ar"/', $html, 'The current language is marked');
        $this->assertStringContainsString(e(route(Locales::SWITCH_ROUTE, ['locale' => Locales::EN, Locales::TARGET_PARAM => '/account'])), $html);
    }

    #[Test]
    public function choosing_a_language_on_the_account_page_saves_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route(AdminNavigation::ACCOUNT_ROUTE))
            ->assertOk()
            ->assertSee('<h2 id="language-heading" class="font-display text-3xl font-light">Language</h2>', false)
            ->assertSee('العربية');

        $this->actingAs($user)
            ->get(route(Locales::SWITCH_ROUTE, ['locale' => Locales::AR, Locales::TARGET_PARAM => '/account']))
            ->assertRedirect('/account')
            ->assertCookie(Locales::COOKIE, Locales::AR, false);

        $this->assertSame(Locales::AR, $user->fresh()->locale);
        $this->actingAs($user->fresh())->get(route(AdminNavigation::ACCOUNT_ROUTE))->assertSee('حسابك');
    }
}
