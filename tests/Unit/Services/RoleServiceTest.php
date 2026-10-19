<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\Permissions;
use App\Constants\Roles;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(RoleService::class);
    }

    #[Test]
    public function it_syncs_every_role_and_permission_from_the_matrix(): void
    {
        $this->service->syncRolesAndPermissions();

        $this->assertEqualsCanonicalizing(Roles::all(), Role::query()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(Permissions::all(), Permission::query()->pluck('name')->all());

        foreach (Permissions::matrix() as $role => $permissions) {
            $this->assertEqualsCanonicalizing(
                $permissions,
                Role::findByName($role)->permissions->pluck('name')->all(),
                "Permissions for {$role}",
            );
        }

        $this->assertCount(count(Permissions::all()), Role::findByName(Roles::ADMIN)->permissions);
        $this->assertCount(3, Role::findByName(Roles::EDITOR)->permissions);
        $this->assertSame([Permissions::ACCESS_CHAT], Role::findByName(Roles::MEMBER)->permissions->pluck('name')->all());
        $this->assertCount(0, Role::findByName(Roles::APPLICANT)->permissions);
    }

    #[Test]
    public function ensure_role_is_idempotent(): void
    {
        $this->service->ensureRole(Roles::EDITOR);
        $this->service->ensureRole(Roles::EDITOR);

        $this->assertSame(1, Role::query()->where('name', Roles::EDITOR)->count());
        $this->assertCount(3, Role::findByName(Roles::EDITOR)->permissions);
    }

    #[Test]
    public function it_rejects_unknown_roles(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->assign(User::factory()->create(), 'overlord');
    }

    #[Test]
    public function assign_remove_and_sync_change_roles_and_are_audited(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create();

        $this->service->assign($user, Roles::EDITOR, $actor);
        $this->assertTrue($user->fresh()->hasRole(Roles::EDITOR));
        $this->assertTrue($user->fresh()->can(Permissions::MANAGE_CONTENT));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $actor->id,
            'action' => AuditAction::ROLE_ASSIGNED,
            'subject_type' => $user->getMorphClass(),
            'subject_id' => $user->id,
        ]);

        $this->service->remove($user, Roles::EDITOR, $actor);
        $this->assertFalse($user->fresh()->hasRole(Roles::EDITOR));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $actor->id, 'action' => AuditAction::ROLE_REMOVED, 'subject_id' => $user->id]);

        $this->service->sync($user, [Roles::ADMIN, Roles::MEMBER], $actor);
        $this->assertEqualsCanonicalizing([Roles::ADMIN, Roles::MEMBER], $this->service->rolesOf($user->fresh()));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $actor->id, 'action' => AuditAction::ROLES_SYNCED, 'subject_id' => $user->id]);
    }
}
