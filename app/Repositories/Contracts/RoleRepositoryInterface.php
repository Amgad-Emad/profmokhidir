<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;

/**
 * The only place that touches spatie/laravel-permission's role and permission
 * models or the HasRoles methods on User.
 */
interface RoleRepositoryInterface
{
    public function findOrCreateRole(string $name): Role;

    public function findOrCreatePermission(string $name): Permission;

    /**
     * @param  list<string>  $permissions
     */
    public function syncPermissions(Role $role, array $permissions): Role;

    /**
     * @return Collection<int, Role>
     */
    public function allRoles(): Collection;

    /**
     * @return Collection<int, Permission>
     */
    public function allPermissions(): Collection;

    /**
     * @return list<string>
     */
    public function roleNamesOf(User $user): array;

    public function assign(User $user, string $role): User;

    public function remove(User $user, string $role): User;

    /**
     * @param  list<string>  $roles
     */
    public function sync(User $user, array $roles): User;

    public function forgetCachedPermissions(): void;
}
