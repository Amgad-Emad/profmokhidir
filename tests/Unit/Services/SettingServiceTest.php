<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\SettingKeys;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(SettingService::class);
    }

    #[Test]
    public function without_stored_values_the_contact_details_are_empty_and_the_mail_sender_comes_from_config(): void
    {
        $all = $this->service->all();

        $this->assertSame(SettingKeys::all(), array_keys($all));
        $this->assertNull($all[SettingKeys::CONTACT_EMAIL]);
        $this->assertNull($this->service->notificationAddress());
        $this->assertSame(['email' => null, 'phone' => null, 'address' => null], $this->service->contact());
        $this->assertSame(config('mail.from.address'), $this->service->mailSender()['address']);
        $this->assertSame(config('mail.from.name'), $this->service->mailSender()['name']);
        $this->assertSame($this->service->defaults(), $all);
    }

    #[Test]
    public function update_stores_trimmed_values_lower_cases_addresses_audits_the_change_and_flushes_the_cache(): void
    {
        $admin = User::factory()->admin()->create();
        $this->service->all();
        $this->assertTrue(Cache::has(CacheKeys::SETTINGS));

        $result = $this->service->update([
            SettingKeys::CONTACT_EMAIL => '  Contact@Example.com ',
            SettingKeys::CONTACT_PHONE => ' +249 123 ',
            SettingKeys::CONTACT_ADDRESS => '',
            SettingKeys::NOTIFICATION_EMAIL => 'Notices@Example.com',
        ], $admin);

        $this->assertSame('contact@example.com', $result[SettingKeys::CONTACT_EMAIL]);
        $this->assertSame('+249 123', $result[SettingKeys::CONTACT_PHONE]);
        $this->assertNull($result[SettingKeys::CONTACT_ADDRESS]);
        $this->assertSame('notices@example.com', $this->service->notificationAddress());
        $this->assertDatabaseHas('settings', ['key' => SettingKeys::CONTACT_EMAIL, 'value' => 'contact@example.com']);
        $this->assertDatabaseHas('settings', ['key' => SettingKeys::CONTACT_ADDRESS, 'value' => null]);

        $log = $admin->auditLogs()->where('action', AuditAction::SETTINGS_UPDATED)->sole();
        $this->assertSame(['contact_email', 'contact_phone', 'notification_email'], array_keys($log->meta['changes']));
        $this->assertEquals(['from' => null, 'to' => 'contact@example.com'], $log->meta['changes'][SettingKeys::CONTACT_EMAIL]);

        // Saving the same values again changes nothing and is not audited twice.
        $this->service->update([SettingKeys::CONTACT_EMAIL => 'contact@example.com'], $admin);
        $this->assertSame(1, $admin->auditLogs()->where('action', AuditAction::SETTINGS_UPDATED)->count());
    }

    #[Test]
    public function the_stored_mail_sender_is_applied_to_the_running_configuration(): void
    {
        $this->service->update([SettingKeys::MAIL_FROM_NAME => 'The Office', SettingKeys::MAIL_FROM_ADDRESS => 'Office@ProfMokhidir.com']);

        $this->assertSame('office@profmokhidir.com', config('mail.from.address'));
        $this->assertSame('The Office', config('mail.from.name'));

        // Clearing the sender falls back to the configured default again.
        $this->service->update([SettingKeys::MAIL_FROM_NAME => '', SettingKeys::MAIL_FROM_ADDRESS => '']);
        $defaults = $this->service->defaults();
        $this->assertSame($defaults[SettingKeys::MAIL_FROM_ADDRESS], $this->service->mailSender()['address']);
        $this->assertNotSame('office@profmokhidir.com', $defaults[SettingKeys::MAIL_FROM_ADDRESS]);
    }

    #[Test]
    public function a_stored_sender_is_applied_when_the_mailer_is_first_resolved(): void
    {
        Setting::factory()->mailSender('Stored Sender', 'stored@profmokhidir.com')->create();
        $this->app->forgetInstance('mail.manager');

        $this->app->make('mail.manager');

        $this->assertSame('stored@profmokhidir.com', config('mail.from.address'));
        $this->assertSame('Stored Sender', config('mail.from.name'));
    }

    #[Test]
    public function seed_defaults_only_fills_keys_that_were_never_stored(): void
    {
        Setting::factory()->contactEmail('kept@example.com')->create();

        $this->service->seedDefaults([SettingKeys::CONTACT_EMAIL => 'seed@example.com', SettingKeys::CONTACT_PHONE => '+1 555']);

        $this->assertSame('kept@example.com', $this->service->get(SettingKeys::CONTACT_EMAIL));
        $this->assertSame('+1 555', $this->service->get(SettingKeys::CONTACT_PHONE));
    }

    #[Test]
    public function unknown_keys_are_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->update(['theme' => 'dark']);
    }
}
