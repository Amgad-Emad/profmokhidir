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
    public function only_admins_require_two_factor(): void
    {
        $this->assertTrue($this->service->requiresTwoFactor(User::factory()->admin()->create()));
        $this->assertFalse($this->service->requiresTwoFactor(User::factory()->editor()->create()));
        $this->assertFalse($this->service->requiresTwoFactor(User::factory()->member()->create()));
        $this->assertFalse($this->service->requiresTwoFactor(User::factory()->create()));
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
    public function admins_without_confirmed_two_factor_must_set_it_up(): void
    {
        $this->assertTrue($this->service->mustSetUpTwoFactor(User::factory()->admin()->withoutTwoFactor()->create()));
        $this->assertTrue($this->service->mustSetUpTwoFactor(User::factory()->admin()->withPendingTwoFactor()->create()));
        $this->assertFalse($this->service->mustSetUpTwoFactor(User::factory()->admin()->create()));
        $this->assertFalse($this->service->mustSetUpTwoFactor(User::factory()->editor()->withoutTwoFactor()->create()));
    }

    #[Test]
    public function two_factor_state_exposes_the_qr_code_and_setup_key_only_while_pending(): void
    {
        $pending = $this->service->twoFactorState(User::factory()->admin()->withPendingTwoFactor()->create());

        $this->assertTrue($pending['required']);
        $this->assertTrue($pending['enabled']);
        $this->assertTrue($pending['pending']);
        $this->assertFalse($pending['confirmed']);
        $this->assertStringContainsString('<svg', $pending['qr_code']);
        $this->assertNotEmpty($pending['setup_key']);
        $this->assertCount(8, $pending['recovery_codes']);

        $confirmed = $this->service->twoFactorState(User::factory()->editor()->withTwoFactor()->create());

        $this->assertFalse($confirmed['required']);
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
