<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthenticationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(AuthenticationService::class);
    }

    #[Test]
    public function two_factor_is_recommended_to_admins_only(): void
    {
        $this->assertTrue($this->service->recommendsTwoFactor(User::factory()->admin()->create()));
        $this->assertFalse($this->service->recommendsTwoFactor(User::factory()->editor()->create()));
        $this->assertFalse($this->service->recommendsTwoFactor(User::factory()->member()->create()));
        $this->assertFalse($this->service->recommendsTwoFactor(User::factory()->create()));
    }

    #[Test]
    public function admins_come_without_two_factor_by_default(): void
    {
        $this->assertFalse($this->service->hasConfirmedTwoFactor(User::factory()->admin()->create()));
        $this->assertTrue($this->service->hasConfirmedTwoFactor(User::factory()->admin()->withTwoFactor()->create()));
    }

    #[Test]
    public function staff_are_admins_and_editors(): void
    {
        $this->assertTrue($this->service->isStaff(User::factory()->admin()->create()));
        $this->assertTrue($this->service->isStaff(User::factory()->editor()->create()));
        $this->assertFalse($this->service->isStaff(User::factory()->member()->create()));
        $this->assertFalse($this->service->isStaff(User::factory()->applicant()->create()));
    }

    #[Test]
    public function two_factor_counts_only_once_confirmed(): void
    {
        $confirmed = User::factory()->withTwoFactor()->create();
        $pending = User::factory()->withPendingTwoFactor()->create();
        $off = User::factory()->withoutTwoFactor()->create();

        $this->assertTrue($this->service->hasConfirmedTwoFactor($confirmed));
        $this->assertFalse($this->service->hasPendingTwoFactor($confirmed));

        $this->assertFalse($this->service->hasConfirmedTwoFactor($pending));
        $this->assertTrue($this->service->hasPendingTwoFactor($pending));

        $this->assertFalse($this->service->hasConfirmedTwoFactor($off));
        $this->assertFalse($this->service->hasPendingTwoFactor($off));
    }

    #[Test]
    public function the_dashboard_reminds_admins_without_confirmed_two_factor_until_dismissed(): void
    {
        $this->assertTrue($this->service->shouldRemindTwoFactor(User::factory()->admin()->create()));
        $this->assertTrue($this->service->shouldRemindTwoFactor(User::factory()->admin()->withPendingTwoFactor()->create()));
        $this->assertFalse($this->service->shouldRemindTwoFactor(User::factory()->admin()->withTwoFactor()->create()));
        $this->assertFalse($this->service->shouldRemindTwoFactor(User::factory()->admin()->dismissedTwoFactorReminder()->create()));
        $this->assertFalse($this->service->shouldRemindTwoFactor(User::factory()->editor()->create()));
    }

    #[Test]
    public function dismissing_the_reminder_is_remembered_for_that_user_only(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $dismissed = $this->service->dismissTwoFactorReminder($admin);

        $this->assertNotNull($dismissed->two_factor_reminder_dismissed_at);
        $this->assertFalse($this->service->shouldRemindTwoFactor($admin->fresh()));
        $this->assertTrue($this->service->shouldRemindTwoFactor($other->fresh()));
    }

    #[Test]
    public function two_factor_state_exposes_the_qr_code_and_setup_key_only_while_pending(): void
    {
        $pending = $this->service->twoFactorState(User::factory()->admin()->withPendingTwoFactor()->create());

        $this->assertTrue($pending['recommended']);
        $this->assertTrue($pending['enabled']);
        $this->assertTrue($pending['pending']);
        $this->assertFalse($pending['confirmed']);
        $this->assertStringContainsString('<svg', $pending['qr_code']);
        $this->assertNotEmpty($pending['setup_key']);
        $this->assertCount(8, $pending['recovery_codes']);

        $confirmed = $this->service->twoFactorState(User::factory()->editor()->withTwoFactor()->create());

        $this->assertFalse($confirmed['recommended']);
        $this->assertTrue($confirmed['confirmed']);
        $this->assertNull($confirmed['qr_code']);
        $this->assertNull($confirmed['setup_key']);
        $this->assertCount(8, $confirmed['recovery_codes']);

        $off = $this->service->twoFactorState(User::factory()->withoutTwoFactor()->create());

        $this->assertFalse($off['enabled']);
        $this->assertSame([], $off['recovery_codes']);
    }

    #[Test]
    public function staff_sign_ins_are_audited_with_ip_and_agent(): void
    {
        $admin = User::factory()->admin()->create();

        $log = $this->service->recordLogin($admin, '10.0.0.1', 'TestBrowser/1.0', true);

        $this->assertNotNull($log);
        $this->assertSame(AuditAction::ADMIN_LOGIN, $log->action);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($admin->id, $log->subject_id);
        $this->assertSame(['ip' => '10.0.0.1', 'user_agent' => 'TestBrowser/1.0', 'remembered' => true], $log->meta);

        $editorLog = $this->service->recordLogin(User::factory()->editor()->create());
        $this->assertNotNull($editorLog);
        $this->assertNull($editorLog->meta);
    }

    #[Test]
    public function member_sign_ins_are_not_audited(): void
    {
        $this->assertNull($this->service->recordLogin(User::factory()->member()->create(), '10.0.0.1'));
        $this->assertNull($this->service->recordLogin(User::factory()->create()));
        $this->assertDatabaseCount('audit_logs', 0);
    }
}
