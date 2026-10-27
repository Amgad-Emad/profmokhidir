<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\ContentTypes;
use App\Constants\LaunchChecklist;
use App\Constants\Locales;
use App\Constants\Roles;
use App\Constants\SettingKeys;
use App\Livewire\Admin\Settings\SiteSettings;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Models\Award;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use App\Repositories\Contracts\ContentRepositoryInterface;
use App\Services\LaunchReadinessService;
use App\Services\LocaleService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The admin shell, dashboard, settings, users, community and tribute screens
 * in Arabic: they follow the signed-in user's saved language.
 */
class ArabicAdminShellTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->arabic()->create();
    }

    #[Test]
    public function the_dashboard_follows_the_admins_language_and_counts_content_needing_arabic(): void
    {
        Publication::factory()->published()->count(2)->create();
        Award::factory()->published()->create();

        $response = $this->actingAs($this->admin)->get(route(AdminNavigation::DASHBOARD_ROUTE))->assertOk();

        $response->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('لوحة التحكم')
            ->assertSee('قبل الإطلاق')
            ->assertSee('يحتاج إلى ترجمة عربية')
            ->assertSee('data-check="'.LaunchChecklist::TRANSLATION.'"', false)
            ->assertSee('3 عناصر تظهر بالإنجليزية في الصفحات العربية إلى أن تُترجم.')
            // The fix opens the type with the most records waiting, filtered to them.
            ->assertSee(e(route(ContentTypes::indexRoute(ContentTypes::PUBLICATION), ['filters' => [ContentRepositoryInterface::FILTER_NEEDS_TRANSLATION => 1]])), false)
            ->assertDontSee('Needs Arabic translation');
    }

    #[Test]
    public function the_translation_check_is_done_once_nothing_waits_for_arabic(): void
    {
        $readiness = app(LaunchReadinessService::class);

        $this->assertTrue($readiness->translations()['done']);
        $this->assertNull($readiness->translations()['url']);

        $award = Award::factory()->published()->create();
        $check = $readiness->translations();

        $this->assertFalse($check['done']);
        $this->assertSame('Needs Arabic translation', $check['label']);
        $this->assertStringContainsString('1 item is shown in English', $check['detail']);
        $this->assertStringContainsString('filters%5B'.ContentRepositoryInterface::FILTER_NEEDS_TRANSLATION.'%5D=1', (string) $check['url']);

        $award->setTranslation('name', Locales::AR, 'جائزة')
            ->setTranslation('organisation', Locales::AR, 'جامعة')
            ->setTranslation('description', Locales::AR, $award->description === null ? null : 'وصف')
            ->save();

        $this->assertTrue($readiness->translations()['done']);
        $this->assertContains(LaunchChecklist::TRANSLATION, array_column($readiness->checklist()['checks'], 'key'));
    }

    #[Test]
    public function the_arabic_digits_setting_saves_and_applies_only_on_arabic_pages(): void
    {
        $locale = app(LocaleService::class);
        App::setLocale(Locales::AR);
        $this->assertSame('12', $locale->digits(12));

        Livewire::actingAs($this->admin)->test(SiteSettings::class)
            ->assertSet('form.'.SettingKeys::ARABIC_DIGITS, Locales::DIGITS_LATIN)
            ->set('form.'.SettingKeys::ARABIC_DIGITS, 'roman')
            ->call('save')
            ->assertHasErrors(['form.'.SettingKeys::ARABIC_DIGITS])
            ->set('form.'.SettingKeys::ARABIC_DIGITS, Locales::DIGITS_ARABIC)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(Locales::DIGITS_ARABIC, app(SettingService::class)->get(SettingKeys::ARABIC_DIGITS));

        App::setLocale(Locales::AR);
        $this->assertSame('١٢ من ٣٠', $locale->digits('12 من 30'));

        App::setLocale(Locales::EN);
        $this->assertSame('12 of 30', $locale->digits('12 of 30'));
    }

    #[Test]
    public function the_settings_screen_renders_in_arabic_with_the_digits_choice(): void
    {
        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS)))->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('إعدادات الموقع')
            ->assertSee('بيانات التواصل العامة')
            ->assertSee('data-arabic-digits-settings', false)
            ->assertSee('الأرقام في الصفحات العربية')
            ->assertSee('الأرقام الهندية (٠–٩)')
            ->assertSee('حفظ الإعدادات');
    }

    #[Test]
    public function the_english_settings_screen_offers_the_digits_choice_in_english(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS)))->assertOk()
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertSee('Digits on Arabic pages')
            ->assertSee('Western (0–9)')
            ->assertSee('Years, citations, DOIs and data always stay in Western digits.');
    }

    #[Test]
    public function the_users_screen_renders_in_arabic_and_shows_each_users_language(): void
    {
        User::factory()->editor()->arabic()->create(['name' => 'Huda Editor']);

        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::USERS)))->assertOk()
            ->assertSee('المستخدمون والأدوار')
            ->assertSee('دعوة عضو في الفريق')
            ->assertSee('data-user-locale="ar"', false)
            ->assertSee('العربية');
    }

    #[Test]
    public function a_staff_invitation_keeps_the_chosen_language(): void
    {
        Notification::fake();

        Livewire::actingAs($this->admin)->test(UsersIndex::class)
            ->call('openInvite')
            ->assertSet('invite.locale', Locales::AR)
            ->set('invite.name', 'Samir Editor')
            ->set('invite.email', 'samir@example.com')
            ->set('invite.role', Roles::EDITOR)
            ->call('sendInvite')
            ->assertHasNoErrors();

        $this->assertSame(Locales::AR, User::query()->where('email', 'samir@example.com')->value('locale'));
    }

    #[Test]
    public function the_community_and_tribute_screens_render_in_arabic_with_visitor_text_left_to_its_own_direction(): void
    {
        User::factory()->applicant()->create(['reason_for_joining' => 'I studied under him.']);
        Tribute::factory()->pending()->create(['message' => 'He taught us patience.']);

        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::APPLICATIONS)))->assertOk()
            ->assertSee('طلبات الانضمام والأعضاء')
            ->assertSee('سبب الانضمام')
            ->assertSee('dir="auto">I studied under him.', false);

        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::CHAT_REPORTS)))->assertOk()
            ->assertSee('بلاغات المحادثة');

        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::TRIBUTES)))->assertOk()
            ->assertSee('مراجعة كلمات الوفاء')
            ->assertSee('<blockquote dir="auto"', false)
            ->assertSee('He taught us patience.');
    }
}
