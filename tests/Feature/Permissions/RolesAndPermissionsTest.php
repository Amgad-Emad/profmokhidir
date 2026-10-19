<?php

namespace Tests\Feature\Permissions;

use App\Constants\CommunityStatus;
use App\Constants\Permissions;
use App\Constants\Roles;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_seeder_creates_every_role_and_permission_from_the_constants(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertEqualsCanonicalizing(Roles::all(), Role::query()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(Permissions::all(), Permission::query()->pluck('name')->all());

        foreach (Permissions::matrix() as $role => $permissions) {
            $this->assertEqualsCanonicalizing(
                $permissions,
                Role::findByName($role)->permissions->pluck('name')->all(),
                "Permissions for role {$role}",
            );
        }
    }

    #[Test]
    public function the_seeder_is_idempotent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(count(Roles::all()), Role::query()->count());
        $this->assertSame(count(Permissions::all()), Permission::query()->count());
    }

    #[Test]
    public function admins_hold_every_permission_and_editors_only_content_ones(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();

        foreach (Permissions::all() as $permission) {
            $this->assertTrue($admin->can($permission), "admin lacks {$permission}");
        }

        $this->assertTrue($editor->can(Permissions::ACCESS_ADMIN));
        $this->assertTrue($editor->can(Permissions::MANAGE_CONTENT));
        $this->assertTrue($editor->can(Permissions::MODERATE_TRIBUTES));
        $this->assertFalse($editor->can(Permissions::MANAGE_USERS));
        $this->assertFalse($editor->can(Permissions::MANAGE_COMMUNITY));
        $this->assertFalse($editor->can(Permissions::MODERATE_CHAT));
        $this->assertFalse($editor->can(Permissions::ACCESS_CHAT));
    }

    #[Test]
    public function members_and_applicants_have_no_admin_permissions(): void
    {
        $member = User::factory()->member()->create();
        $applicant = User::factory()->applicant()->create();

        $this->assertTrue($member->can(Permissions::ACCESS_CHAT));
        $this->assertFalse($member->can(Permissions::ACCESS_ADMIN));
        $this->assertFalse($member->can(Permissions::MANAGE_CONTENT));

        foreach (Permissions::all() as $permission) {
            $this->assertFalse($applicant->can($permission), "applicant has {$permission}");
        }
    }

    #[Test]
    public function the_access_admin_gate_follows_the_staff_roles(): void
    {
        $this->assertTrue(Gate::forUser(User::factory()->admin()->create())->allows('access-admin'));
        $this->assertTrue(Gate::forUser(User::factory()->editor()->create())->allows('access-admin'));
        $this->assertFalse(Gate::forUser(User::factory()->member()->create())->allows('access-admin'));
        $this->assertFalse(Gate::forUser(User::factory()->applicant()->create())->allows('access-admin'));
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('access-admin'));
    }

    #[Test]
    public function chat_access_requires_an_approved_member_or_an_admin(): void
    {
        $this->assertTrue(User::factory()->member()->create()->canAccessChat());
        $this->assertTrue(User::factory()->admin()->create()->canAccessChat());
        $this->assertFalse(User::factory()->editor()->create()->canAccessChat());
        $this->assertFalse(User::factory()->applicant()->create()->canAccessChat());
        $this->assertFalse(User::factory()->rejectedApplicant()->create()->canAccessChat());
        $this->assertFalse(User::factory()->suspendedMember()->create()->canAccessChat());

        // Holding the member role is not enough once the community status is no longer approved.
        $suspended = User::factory()->member()->create(['community_status' => CommunityStatus::Suspended]);
        $this->assertFalse($suspended->canAccessChat());
    }

    #[Test]
    public function users_table_has_no_role_column(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'role'));
        $this->assertSame(Roles::ADMIN, User::factory()->admin()->create()->primaryRole());
        $this->assertSame('Administrator', User::factory()->admin()->create()->primaryRoleLabel());
        $this->assertSame('User', User::factory()->create()->primaryRoleLabel());
    }
}
