<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\AuditAction;
use App\Constants\SettingKeys;
use App\Constants\TributeRelationship;
use App\Livewire\Admin\Settings\SiteSettings;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\TributeAwaitingReview;
use App\Services\AboutPageService;
use App\Services\AnalyticsService;
use App\Services\ProfileService;
use App\Services\SettingService;
use App\Services\TributeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    #[Test]
    public function the_screen_lives_at_admin_settings_is_admin_only_and_starts_from_the_defaults(): void
    {
        $this->assertSame(url('/admin/settings'), route(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS)));

        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS)))->assertOk()
            ->assertSee('Site settings')
            ->assertSee('data-site-settings', false)
            ->assertSee('Public contact details')
            ->assertSee('Notification address')
            ->assertSee('Mail sender name');

        $editor = User::factory()->editor()->create();
        $this->actingAs($editor)->get(route(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS)))->assertForbidden();
        Livewire::actingAs($editor)->test(SiteSettings::class)->assertForbidden();

        Livewire::actingAs($this->admin)->test(SiteSettings::class)
            ->assertSet('form.'.SettingKeys::CONTACT_EMAIL, null)
            ->assertSet('form.'.SettingKeys::MAIL_FROM_ADDRESS, config('mail.from.address'));
    }

    #[Test]
    public function saving_validates_stores_audits_and_feeds_the_contact_and_notification_code(): void
    {
        Notification::fake();

        Livewire::actingAs($this->admin)->test(SiteSettings::class)
            ->set('form.'.SettingKeys::CONTACT_EMAIL, 'not an email')
            ->set('form.'.SettingKeys::CONTACT_PHONE, 'call me')
            ->set('form.'.SettingKeys::NOTIFICATION_EMAIL, 'nope')
            ->set('form.'.SettingKeys::MAIL_FROM_ADDRESS, 'nope')
            ->call('save')
            ->assertHasErrors(['form.'.SettingKeys::CONTACT_EMAIL, 'form.'.SettingKeys::CONTACT_PHONE, 'form.'.SettingKeys::NOTIFICATION_EMAIL, 'form.'.SettingKeys::MAIL_FROM_ADDRESS])
            ->set('form.'.SettingKeys::CONTACT_EMAIL, 'Office@Example.com')
            ->set('form.'.SettingKeys::CONTACT_PHONE, '+249 (0)12 345')
            ->set('form.'.SettingKeys::CONTACT_ADDRESS, "Faculty of Agriculture\nKhartoum")
            ->set('form.'.SettingKeys::NOTIFICATION_EMAIL, 'notices@example.com')
            ->set('form.'.SettingKeys::MAIL_FROM_NAME, 'The Khidir Office')
            ->set('form.'.SettingKeys::MAIL_FROM_ADDRESS, 'noreply@profmokhidir.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Site settings saved.')
            ->assertSet('form.'.SettingKeys::CONTACT_EMAIL, 'office@example.com');

        $this->assertDatabaseHas('settings', ['key' => SettingKeys::NOTIFICATION_EMAIL, 'value' => 'notices@example.com']);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::SETTINGS_UPDATED]);

        // Mail sender.
        $this->assertSame('noreply@profmokhidir.com', config('mail.from.address'));
        $this->assertSame('The Khidir Office', config('mail.from.name'));
        $this->assertSame(['name' => 'The Khidir Office', 'address' => 'noreply@profmokhidir.com'], app(SettingService::class)->mailSender());

        // The About page shows only the contact details entered with it (Pages › About), never the site's.
        Profile::factory()->published()->create(['contact_email_public' => null, 'contact_note' => null]);
        $contact = app(AboutPageService::class)->contact(Profile::query()->first());
        $this->assertSame(['email' => null, 'note' => null, 'phone' => null, 'address' => null], $contact);
        $this->get(route('about'))->assertOk()->assertDontSee('mailto:office@example.com', false)->assertDontSee('data-contact-phone', false)->assertDontSee('Faculty of Agriculture');

        // The profile's own email still wins.
        Profile::query()->first()->update(['contact_email_public' => 'prof@example.com']);
        app(ProfileService::class)->flushCache();
        $this->assertSame('prof@example.com', app(AboutPageService::class)->contact(Profile::query()->first())['email']);

        // Tribute notices go to the notification address, not to every moderator.
        app(TributeService::class)->submit(['name' => 'A', 'relationship' => TributeRelationship::Friend, 'message' => 'Hello'], '203.0.113.9');
        Notification::assertSentOnDemand(TributeAwaitingReview::class, fn (TributeAwaitingReview $n, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'notices@example.com');
        Notification::assertNotSentTo($this->admin, TributeAwaitingReview::class);
    }

    #[Test]
    public function the_analytics_switch_is_off_by_default_explains_what_is_collected_and_saves(): void
    {
        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS)))->assertOk()
            ->assertSee('data-analytics-settings', false)
            ->assertSee('Record visitor analytics')
            ->assertSee('no cookies, no IP addresses')
            ->assertSee('once the client has approved');

        $this->assertFalse(app(AnalyticsService::class)->enabled());

        Livewire::actingAs($this->admin)->test(SiteSettings::class)
            ->assertSet('form.'.SettingKeys::ANALYTICS_ENABLED, false)
            ->set('form.'.SettingKeys::ANALYTICS_ENABLED, true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.'.SettingKeys::ANALYTICS_ENABLED, true);

        $this->assertTrue(app(AnalyticsService::class)->enabled());
        $this->assertSame('1', app(SettingService::class)->get(SettingKeys::ANALYTICS_ENABLED));

        Livewire::actingAs($this->admin)->test(SiteSettings::class)
            ->set('form.'.SettingKeys::ANALYTICS_ENABLED, false)
            ->call('save');

        $this->assertFalse(app(AnalyticsService::class)->enabled());
    }
}
