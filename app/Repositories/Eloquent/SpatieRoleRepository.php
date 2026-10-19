<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class SpatieRoleRepository implements RoleRepositoryInterface
{
    public function __construct(private readonly PermissionRegistrar $registrar) {}

    public function findOrCreateRole(string $name): RoleContract
    {
        return Role::findOrCreate($name);
    }

    public function findOrCreatePermission(string $name): PermissionContract
    {
        return Permission::findOrCreate($name);
    }

    public function syncPermissions(RoleContract $role, array $permissions): RoleContract
    {
        $models = array_map(fn (string $name): PermissionContract => $this->findOrCreatePermission($name), $permissions);

        $role->syncPermissions($models);
        $this->forgetCachedPermissions();

        return $role;
    }

    public function allRoles(): Collection
    {
        return Role::query()->with('permissions')->orderBy('name')->get();
    }

    public function allPermissions(): Collection
    {
        return Permission::query()->orderBy('name')->get();
    }

    public function roleNamesOf(User $user): array
    {
        return $user->getRoleNames()->values()->all();
    }

    public function assign(User $user, string $role): User
    {
        $user->assignRole($this->findOrCreateRole($role));

        return $user;
    }

    public function remove(User $user, string $role): User
    {
        $user->removeRole($role);

        return $user;
    }

    public function sync(User $user, array $roles): User
    {
        $user->syncRoles(array_map(fn (string $role): RoleContract => $this->findOrCreateRole($role), $roles));

        return $user;
    }

    public function forgetCachedPermissions(): void
    {
        $this->registrar->forgetCachedPermissions();
    }
}
