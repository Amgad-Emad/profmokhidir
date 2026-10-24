<?php

namespace Tests\Feature\Auth;

use App\Constants\AuditAction;
use App\Listeners\LogAdminLogin;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminLoginAuditTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_listener_is_registered_for_the_login_event(): void
    {
        Event::fake([Login::class]);
        Event::assertListening(Login::class, LogAdminLogin::class);
    }

    #[Test]
    public function an_editor_password_login_is_written_to_the_audit_log(): void
    {
        $editor = User::factory()->editor()->create(['password' => 'secret-password']);

        $this->withHeader('User-Agent', 'AuditTest/1.0')->post(route('login'), [
            'email' => $editor->email,
            'password' => 'secret-password',
            'remember' => '1',
        ])->assertRedirect('/admin');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $editor->id,
            'action' => AuditAction::ADMIN_LOGIN,
            'subject_type' => $editor->getMorphClass(),
            'subject_id' => $editor->id,
        ]);

        $meta = $editor->auditLogs()->latest('id')->first()->meta;
        $this->assertSame('AuditTest/1.0', $meta['user_agent']);
        $this->assertTrue($meta['remembered']);
        $this->assertArrayHasKey('ip', $meta);
    }

    #[Test]
    public function an_admin_is_logged_once_the_two_factor_challenge_succeeds(): void
    {
        $admin = User::factory()->admin()->withTwoFactor()->create(['password' => 'secret-password']);

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret-password'])
            ->assertRedirect(route('two-factor.login'));

        $this->assertDatabaseMissing('audit_logs', ['user_id' => $admin->id, 'action' => AuditAction::ADMIN_LOGIN]);

        $this->post(route('two-factor.login'), ['recovery_code' => $admin->recoveryCodes()[0]])
            ->assertRedirect('/admin');

        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => AuditAction::ADMIN_LOGIN]);
    }

    #[Test]
    public function member_and_failed_logins_are_not_audited(): void
    {
        $member = User::factory()->member()->create(['password' => 'secret-password']);

        $this->post(route('login'), ['email' => $member->email, 'password' => 'secret-password']);
        $this->post(route('login'), ['email' => $member->email, 'password' => 'wrong']);

        $this->assertDatabaseCount('audit_logs', 0);
    }
}
