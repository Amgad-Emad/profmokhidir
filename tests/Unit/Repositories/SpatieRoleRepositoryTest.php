<?php

namespace Tests\Unit\Repositories;

use App\Constants\Permissions;
use App\Constants\Roles;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Eloquent\SpatieRoleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SpatieRoleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private RoleRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(RoleRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_spatie_implementation(): void
    {
        $this->assertInstanceOf(SpatieRoleRepository::class, $this->repository);
    }

    #[Test]
    public function find_or_create_is_idempotent_for_roles_and_permissions(): void
    {
        $role = $this->repository->findOrCreateRole(Roles::EDITOR);
        $again = $this->repository->findOrCreateRole(Roles::EDITOR);

        $this->assertSame($role->getKey(), $again->getKey());
        $this->assertSame(1, Role::query()->count());

        $permission = $this->repository->findOrCreatePermission(Permissions::MANAGE_CONTENT);
        $this->repository->findOrCreatePermission(Permissions::MANAGE_CONTENT);

        $this->assertSame(Permissions::MANAGE_CONTENT, $permission->name);
        $this->assertSame(1, Permission::query()->count());
    }

    #[Test]
    public function sync_permissions_replaces_the_role_permissions(): void
    {
        $role = $this->repository->findOrCreateRole(Roles::EDITOR);

        $this->repository->syncPermissions($role, [Permissions::MANAGE_CONTENT, Permissions::MODERATE_TRIBUTES]);
        $this->assertEqualsCanonicalizing(
            [Permissions::MANAGE_CONTENT, Permissions::MODERATE_TRIBUTES],
            $role->fresh()->permissions->pluck('name')->all(),
        );

        $this->repository->syncPermissions($role, [Permissions::ACCESS_ADMIN]);
        $this->assertSame([Permissions::ACCESS_ADMIN], $role->fresh()->permissions->pluck('name')->all());
    }

    #[Test]
    public function it_lists_all_roles_and_permissions_sorted_by_name(): void
    {
        $this->repository->findOrCreateRole(Roles::MEMBER);
        $this->repository->findOrCreateRole(Roles::ADMIN);
        $this->repository->findOrCreatePermission(Permissions::MANAGE_USERS);
        $this->repository->findOrCreatePermission(Permissions::ACCESS_CHAT);

        $this->assertSame([Roles::ADMIN, Roles::MEMBER], $this->repository->allRoles()->pluck('name')->all());
        $this->assertTrue($this->repository->allRoles()->first()->relationLoaded('permissions'));
        $this->assertSame([Permissions::ACCESS_CHAT, Permissions::MANAGE_USERS], $this->repository->allPermissions()->pluck('name')->all());
    }

    #[Test]
    public function it_assigns_removes_and_syncs_roles_on_a_user(): void
    {
        $user = User::factory()->create();

        $this->assertSame([], $this->repository->roleNamesOf($user));

        $this->repository->assign($user, Roles::EDITOR);
        $this->assertTrue($user->hasRole(Roles::EDITOR));
        $this->assertSame([Roles::EDITOR], $this->repository->roleNamesOf($user));

        $this->repository->sync($user, [Roles::ADMIN, Roles::MEMBER]);
        $this->assertEqualsCanonicalizing([Roles::ADMIN, Roles::MEMBER], $this->repository->roleNamesOf($user->fresh()));
        $this->assertFalse($user->fresh()->hasRole(Roles::EDITOR));

        $this->repository->remove($user, Roles::MEMBER);
        $this->assertSame([Roles::ADMIN], $this->repository->roleNamesOf($user->fresh()));
    }

    #[Test]
    public function forget_cached_permissions_lets_new_permissions_take_effect(): void
    {
        $user = User::factory()->create();
        $role = $this->repository->findOrCreateRole(Roles::EDITOR);
        $this->repository->assign($user, Roles::EDITOR);

        $this->assertFalse($user->can(Permissions::MANAGE_CONTENT));

        $this->repository->syncPermissions($role, [Permissions::MANAGE_CONTENT]);
        $this->repository->forgetCachedPermissions();

        $this->assertTrue($user->fresh()->can(Permissions::MANAGE_CONTENT));
    }
}
