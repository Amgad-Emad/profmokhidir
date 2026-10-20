<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\AuditAction;
use App\Constants\CommunityStatus;
use App\Constants\Permissions;
use App\Constants\Roles;
use App\Http\Requests\Admin\StaffRoleRequest;
use App\Livewire\Admin\Users\Index;
use App\Models\User;
use App\Notifications\StaffInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsersScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = User::factory()->admin()->create(['name' => 'Root Admin']);
    }

    #[Test]
    public function the_screen_lives_at_admin_users_and_is_admin_only(): void
    {
        $this->assertSame(url('/admin/users'), route(AdminNavigation::routeFor(AdminNavigation::USERS)));

        $editor = User::factory()->editor()->create(['name' => 'Edna Editor', 'email' => 'edna@example.com']);
        User::factory()->member()->create(['name' => 'Amina Member']);
        User::factory()->editor()->invited()->create(['name' => 'Invited Person']);
        User::factory()->editor()->deactivated()->create(['name' => 'Gone Person']);

        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::USERS)))->assertOk()
            ->assertSee('Users &amp; roles', false)
            ->assertSee('data-invite', false)
            ->assertSee('Edna Editor')
            ->assertSee('edna@example.com')
            ->assertSee('Amina Member')
            ->assertSee('Invitation pending')
            ->assertSee('Deactivated')
            ->assertSee('(you)')
            ->assertSee('data-nav-item="users"', false)
            ->assertSee('data-nav-item="site-settings"', false);

        $this->actingAs($editor)->get(route(AdminNavigation::routeFor(AdminNavigation::USERS)))->assertForbidden();
        Livewire::actingAs($editor)->test(Index::class)->assertForbidden();
    }

    #[Test]
    public function search_and_filters_narrow_the_list(): void
    {
        User::factory()->editor()->create(['name' => 'Edna Editor']);
        User::factory()->member()->create(['name' => 'Amina Member']);
        User::factory()->applicant()->create(['name' => 'Omar Applicant']);

        Livewire::actingAs($this->admin)->test(Index::class)
            ->assertSee('Edna Editor')->assertSee('Amina Member')->assertSee('Omar Applicant')
            ->set('search', 'edna')
            ->assertSee('Edna Editor')->assertDontSee('Amina Member')
            ->set('search', '')
            ->set('role', Roles::MEMBER)
            ->assertSee('Amina Member')->assertDontSee('Edna Editor')
            ->set('role', '')
            ->set('status', CommunityStatus::Pending->value)
            ->assertSee('Omar Applicant')->assertDontSee('Amina Member')
            ->set('search', 'nobody')
            ->assertSee('Nobody matches your search.')
            ->call('clearFilters')
            ->assertSet('search', '')->assertSet('role', '')->assertSet('status', '')
            ->assertSee('Edna Editor');
    }

    #[Test]
    public function inviting_a_staff_member_validates_creates_the_account_and_emails_a_set_password_link(): void
    {
        $component = Livewire::actingAs($this->admin)->test(Index::class)
            ->call('openInvite')
            ->assertSet('inviting', true)
            ->assertSee('data-invite-dialog', false)
            ->set('invite.name', '')
            ->set('invite.email', 'not-an-email')
            ->set('invite.role', Roles::MEMBER)
            ->call('sendInvite')
            ->assertHasErrors(['invite.name', 'invite.email', 'invite.role'])
            ->set('invite.name', 'New Admin')
            ->set('invite.email', $this->admin->email)
            ->set('invite.role', Roles::ADMIN)
            ->call('sendInvite')
            ->assertHasErrors(['invite.email'])
            ->assertSee('An account with this email already exists');

        $component->set('invite.email', 'New.Admin@Example.com')
            ->call('sendInvite')
            ->assertHasNoErrors()
            ->assertSet('inviting', false)
            ->assertSee('New Admin has been invited as administrator.')
            ->assertSee('Invitation pending');

        $user = User::query()->where('email', 'new.admin@example.com')->sole();
        $this->assertTrue($user->hasRole(Roles::ADMIN));
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::USER_INVITED, 'subject_id' => $user->id]);
        Notification::assertSentTo($user, StaffInvitation::class, fn (StaffInvitation $n): bool => $n->role === Roles::ADMIN);
    }

    #[Test]
    public function roles_are_changed_from_a_dialog_with_the_safeguards_explained(): void
    {
        $editor = User::factory()->editor()->create(['name' => 'Edna Editor']);

        Livewire::actingAs($this->admin)->test(Index::class)
            ->call('editRole', $editor->id)
            ->assertSet('roleEditingId', $editor->id)
            ->assertSet('newRole', Roles::EDITOR)
            ->assertSee('Change the role of Edna Editor')
            ->set('newRole', 'owner')
            ->call('saveRole')
            ->assertHasErrors(['role'])
            ->set('newRole', Roles::ADMIN)
            ->call('saveRole')
            ->assertHasNoErrors()
            ->assertSet('roleEditingId', null)
            ->assertSee('Edna Editor is now administrator.');

        $this->assertTrue($editor->fresh()->hasRole(Roles::ADMIN));
        $this->assertFalse($editor->fresh()->hasRole(Roles::EDITOR));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::ROLES_SYNCED, 'subject_id' => $editor->id]);

        // Withdrawing dashboard access keeps the account.
        Livewire::actingAs($this->admin)->test(Index::class)
            ->call('editRole', $editor->id)
            ->set('newRole', StaffRoleRequest::NONE)
            ->call('saveRole')
            ->assertHasNoErrors()
            ->assertSee('Edna Editor is now without dashboard access.');

        $this->assertSame([], $editor->fresh()->getRoleNames()->all());
    }

    #[Test]
    public function nobody_can_change_or_deactivate_themselves_and_deactivation_can_be_undone(): void
    {
        $other = User::factory()->admin()->create(['name' => 'Second Admin']);

        // The signed-in admin's own row has no actions and the component refuses.
        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::USERS)))
            ->assertDontSee('of Root Admin</span>', false)
            ->assertSee('of Second Admin</span>', false);

        Livewire::actingAs($this->admin)->test(Index::class)->call('editRole', $this->admin->id)->assertForbidden();
        Livewire::actingAs($this->admin)->test(Index::class)->call('confirm', Index::ACTION_DEACTIVATE, $this->admin->id)->assertForbidden();

        Livewire::actingAs($this->admin)->test(Index::class)
            ->call('confirm', Index::ACTION_DEACTIVATE, $other->id)
            ->assertSee('Deactivate this account?')
            ->call('runConfirmed')
            ->assertHasNoErrors()
            ->assertSee('Second Admin&#039;s account has been deactivated.', false)
            ->assertSee('data-reactivate', false)
            ->assertSee('Active administrators: <span class="font-mono text-evergreen">1</span>', false);

        $this->assertFalse($other->fresh()->isActive());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::USER_DEACTIVATED, 'subject_id' => $other->id]);

        Livewire::actingAs($this->admin)->test(Index::class)
            ->call('confirm', Index::ACTION_REACTIVATE, $other->id)
            ->call('runConfirmed')
            ->assertHasNoErrors()
            ->assertSee('Second Admin&#039;s account has been reactivated.', false);

        $this->assertTrue($other->fresh()->isActive());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::USER_REACTIVATED, 'subject_id' => $other->id]);
    }

    #[Test]
    public function the_last_active_admin_safeguard_is_shown_as_an_error_in_the_dialogs(): void
    {
        // Only administrators hold "manage users", so the guard can normally only
        // fire through a direct grant; it is still enforced by the service.
        $manager = User::factory()->editor()->create(['name' => 'Manager']);
        $manager->givePermissionTo(Permissions::MANAGE_USERS);

        Livewire::actingAs($manager)->test(Index::class)
            ->call('editRole', $this->admin->id)
            ->set('newRole', Roles::EDITOR)
            ->call('saveRole')
            ->assertHasErrors(['newRole'])
            ->assertSee('last active administrator')
            ->call('cancelRole')
            ->call('confirm', Index::ACTION_DEACTIVATE, $this->admin->id)
            ->call('runConfirmed')
            ->assertHasErrors(['action'])
            ->assertSee('data-action-error', false)
            ->assertSee('last active administrator');

        $this->assertTrue($this->admin->fresh()->isAdmin());
        $this->assertTrue($this->admin->fresh()->isActive());
    }

    #[Test]
    public function a_pending_invitation_can_be_resent_from_the_list(): void
    {
        $invited = User::factory()->editor()->invited()->create(['name' => 'Invited Person']);
        $active = User::factory()->editor()->create(['name' => 'Active Person']);

        $this->actingAs($this->admin)->get(route(AdminNavigation::routeFor(AdminNavigation::USERS)))
            ->assertSee('data-user="'.$invited->id.'"', false)
            ->assertSee('to Invited Person</span>', false)
            ->assertDontSee('to Active Person</span>', false);

        Livewire::actingAs($this->admin)->test(Index::class)
            ->call('confirm', Index::ACTION_RESEND, $invited->id)
            ->assertSee('Resend the invitation?')
            ->call('runConfirmed')
            ->assertHasNoErrors()
            ->assertSee('Invited Person has been sent a new invitation link.');

        Notification::assertSentTo($invited, StaffInvitation::class);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $this->admin->id, 'action' => AuditAction::USER_INVITE_RESENT, 'subject_id' => $invited->id]);

        Livewire::actingAs($this->admin)->test(Index::class)
            ->call('confirm', Index::ACTION_RESEND, $active->id)
            ->call('runConfirmed')
            ->assertHasErrors(['action'])
            ->assertSee('already set up their account');
    }
}
