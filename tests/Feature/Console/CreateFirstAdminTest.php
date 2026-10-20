<?php

namespace Tests\Feature\Console;

use App\Constants\AuditAction;
use App\Constants\Permissions;
use App\Constants\Roles;
use App\Models\User;
use App\Notifications\StaffInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateFirstAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    #[Test]
    public function it_asks_for_name_and_email_syncs_roles_and_invites_the_administrator(): void
    {
        $this->assertSame(0, Role::query()->count());

        $this->artisan('admin:create-first')
            ->expectsQuestion('Name', ' Site Owner ')
            ->expectsQuestion('Email address', 'Owner@ProfMokhidir.com')
            ->expectsOutputToContain('Site Owner <owner@profmokhidir.com> has been created as an administrator.')
            ->expectsOutputToContain('set-password link has been emailed')
            ->expectsOutputToContain('Two-factor authentication is required')
            ->assertSuccessful();

        $user = User::query()->where('email', 'owner@profmokhidir.com')->sole();
        $this->assertTrue($user->hasRole(Roles::ADMIN));
        $this->assertTrue($user->can(Permissions::MANAGE_USERS));
        $this->assertNull($user->email_verified_at);
        $this->assertSame(count(Roles::all()), Role::query()->count());
        $this->assertDatabaseHas('audit_logs', ['user_id' => null, 'action' => AuditAction::USER_INVITED, 'subject_id' => $user->id]);

        Notification::assertSentTo($user, StaffInvitation::class, fn (StaffInvitation $n): bool => $n->role === Roles::ADMIN && Password::broker()->tokenExists($user, $n->token));
    }

    #[Test]
    public function options_skip_the_questions_and_invalid_input_is_refused(): void
    {
        $this->artisan('admin:create-first', ['--name' => 'Owner', '--email' => 'not-an-email'])
            ->expectsOutputToContain('email')
            ->assertExitCode(2);

        $this->assertSame(0, User::query()->count());
        Notification::assertNothingSent();

        $this->artisan('admin:create-first', ['--name' => 'Owner', '--email' => 'owner@example.com'])->assertSuccessful();
        $this->assertSame(1, User::query()->count());
    }

    #[Test]
    public function it_refuses_when_an_active_administrator_exists_unless_forced(): void
    {
        User::factory()->admin()->create();

        $this->artisan('admin:create-first', ['--name' => 'Owner', '--email' => 'owner@example.com'])
            ->expectsOutputToContain('already exist')
            ->assertFailed();

        $this->assertSame(1, User::query()->count());

        $this->artisan('admin:create-first', ['--name' => 'Owner', '--email' => 'owner@example.com', '--force' => true])->assertSuccessful();
        $this->assertSame(2, User::query()->count());

        // A deactivated administrator does not count.
        User::query()->update(['deactivated_at' => now()]);
        $this->artisan('admin:create-first', ['--name' => 'Again', '--email' => 'again@example.com'])->assertSuccessful();
    }
}
