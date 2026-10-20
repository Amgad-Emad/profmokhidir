<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\CommunityStatus;
use App\Constants\Roles;
use App\Exceptions\UserManagementException;
use App\Models\User;
use App\Notifications\StaffInvitation;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * UserService against the database: invitations, role changes, deactivation
 * and the administrator safeguards.
 */
class StaffAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $service;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->service = $this->app->make(UserService::class);
        $this->admin = User::factory()->admin()->create(['name' => 'Root Admin']);
    }

    #[Test]
    public function invite_creates_an_unverified_staff_account_sends_a_working_set_password_link_and_is_audited(): void
    {
        $user = $this->service->invite(['name' => ' Edna Editor ', 'email' => ' Edna@Example.com ', 'role' => Roles::EDITOR], $this->admin);

        $this->assertSame('Edna Editor', $user->name);
        $this->assertSame('edna@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->isActive());
        $this->assertSame(Roles::EDITOR, $this->service->staffRoleOf($user));
        $this->assertTrue($this->service->hasPendingInvitation($user));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::USER_INVITED, 'subject_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::ROLE_ASSIGNED, 'subject_id' => $user->id]);

        Notification::assertSentTo($user, StaffInvitation::class, function (StaffInvitation $notification) use ($user): bool {
            return $notification->role === Roles::EDITOR && Password::broker()->tokenExists($user, $notification->token);
        });
    }

    #[Test]
    public function invitations_are_only_for_staff_roles(): void
    {
        $this->expectException(UserManagementException::class);

        $this->service->invite(['name' => 'M', 'email' => 'm@example.com', 'role' => Roles::MEMBER], $this->admin);
    }

    #[Test]
    public function resend_invitation_sends_a_new_link_and_refuses_once_the_account_is_set_up(): void
    {
        $invited = User::factory()->editor()->invited()->create();

        $this->service->resendInvitation($invited, $this->admin);

        Notification::assertSentTo($invited, StaffInvitation::class);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::USER_INVITE_RESENT, 'subject_id' => $invited->id]);

        $this->expectException(UserManagementException::class);
        $this->service->resendInvitation(User::factory()->editor()->create(), $this->admin);
    }

    #[Test]
    public function reset_password_marks_an_unverified_email_as_verified(): void
    {
        $invited = User::factory()->editor()->invited()->create();

        $user = $this->service->resetPassword($invited, 'brand-new-password-123');

        $this->assertTrue(Hash::check('brand-new-password-123', $user->password));
        $this->assertNotNull($user->email_verified_at);
    }

    #[Test]
    public function change_staff_role_swaps_the_staff_role_and_keeps_any_community_role(): void
    {
        $other = User::factory()->admin()->create();
        $member = User::factory()->member()->create();

        $this->service->changeStaffRole($member, Roles::EDITOR, $this->admin);
        $this->assertEqualsCanonicalizing([Roles::MEMBER, Roles::EDITOR], $member->fresh()->getRoleNames()->all());

        $this->service->changeStaffRole($member, Roles::ADMIN, $this->admin);
        $this->assertEqualsCanonicalizing([Roles::MEMBER, Roles::ADMIN], $member->fresh()->getRoleNames()->all());

        $this->service->changeStaffRole($member, null, $this->admin);
        $this->assertSame([Roles::MEMBER], $member->fresh()->getRoleNames()->all());
        $this->assertSame(CommunityStatus::Approved, $member->fresh()->community_status);

        $this->service->changeStaffRole($other, Roles::EDITOR, $this->admin);
        $this->assertSame([Roles::EDITOR], $other->fresh()->getRoleNames()->all());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::ROLES_SYNCED, 'subject_id' => $other->id]);
    }

    #[Test]
    public function an_admin_cannot_change_their_own_role(): void
    {
        $this->expectException(UserManagementException::class);
        $this->expectExceptionMessage('your own role');

        $this->service->changeStaffRole($this->admin, Roles::EDITOR, $this->admin);
    }

    #[Test]
    public function the_last_active_admin_cannot_be_demoted(): void
    {
        $actor = User::factory()->editor()->create();
        User::factory()->admin()->deactivated()->create();

        $this->assertTrue($this->service->isLastActiveAdmin($this->admin));
        $this->assertSame(1, $this->service->countActiveAdmins());

        try {
            $this->service->changeStaffRole($this->admin, Roles::EDITOR, $actor);
            $this->fail('The last active admin was demoted.');
        } catch (UserManagementException $exception) {
            $this->assertStringContainsString('last active administrator', $exception->getMessage());
        }

        $this->assertTrue($this->admin->fresh()->isAdmin());

        // Promoting them to admin again (no change) is harmless.
        $this->service->changeStaffRole($this->admin, Roles::ADMIN, $actor);
        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    #[Test]
    public function deactivate_and_reactivate_are_audited_and_guarded(): void
    {
        $editor = User::factory()->editor()->create();

        $this->service->deactivate($editor, $this->admin);
        $this->assertFalse($editor->fresh()->isActive());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::USER_DEACTIVATED, 'subject_id' => $editor->id]);

        $this->service->reactivate($editor, $this->admin);
        $this->assertTrue($editor->fresh()->isActive());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::USER_REACTIVATED, 'subject_id' => $editor->id]);

        try {
            $this->service->deactivate($this->admin, $this->admin);
            $this->fail('Self-deactivation was allowed.');
        } catch (UserManagementException $exception) {
            $this->assertStringContainsString('your own account', $exception->getMessage());
        }

        $other = User::factory()->admin()->create();

        try {
            $this->service->deactivate($this->admin, $other);
            $this->service->deactivate($other, $this->admin);
            $this->fail('The last active admin was deactivated.');
        } catch (UserManagementException $exception) {
            $this->assertStringContainsString('last active administrator', $exception->getMessage());
        }

        $this->assertFalse($this->admin->fresh()->isActive());
        $this->assertTrue($other->fresh()->isActive());
    }

    #[Test]
    public function search_matches_name_or_email_and_filters_by_role_and_community_status(): void
    {
        User::factory()->editor()->create(['name' => 'Edna Editor', 'email' => 'edna@example.com']);
        User::factory()->member()->create(['name' => 'Amina Member', 'email' => 'amina@example.com']);
        User::factory()->applicant()->create(['name' => 'Omar Applicant', 'email' => 'omar@example.com']);

        $this->assertSame(['Amina Member', 'Edna Editor', 'Omar Applicant', 'Root Admin'], $this->service->search()->pluck('name')->all());
        $this->assertSame(['Edna Editor'], $this->service->search('EDNA')->pluck('name')->all());
        $this->assertSame(['Omar Applicant'], $this->service->search('omar@')->pluck('name')->all());
        $this->assertSame(['Root Admin'], $this->service->search(null, Roles::ADMIN)->pluck('name')->all());
        $this->assertSame(['Omar Applicant'], $this->service->search(null, null, CommunityStatus::Pending)->pluck('name')->all());
        $this->assertSame([], $this->service->search('Amina', Roles::EDITOR)->pluck('name')->all());
    }
}
