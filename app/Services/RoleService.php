<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\Permissions;
use App\Constants\Roles;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use InvalidArgumentException;

/**
 * Roles and permissions (spatie/laravel-permission) are only touched here and
 * in RoleRepository. Role changes are audited.
 */
class RoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Create every role and permission from app/Constants and sync the matrix.
     */
    public function syncRolesAndPermissions(): void
    {
        foreach (Permissions::all() as $permission) {
            $this->roles->findOrCreatePermission($permission);
        }

        foreach (Roles::all() as $role) {
            $this->ensureRole($role);
        }

        $this->roles->forgetCachedPermissions();
    }

    /**
     * Make sure a role exists and carries its permissions from the matrix.
     */
    public function ensureRole(string $role): void
    {
        $this->guardRole($role);

        $this->roles->syncPermissions(
            $this->roles->findOrCreateRole($role),
            Permissions::forRole($role),
        );
    }

    public function assign(User $user, string $role, ?User $actor = null): User
    {
        $this->ensureRole($role);
        $this->roles->assign($user, $role);
        $this->audit->record($actor, AuditAction::ROLE_ASSIGNED, $user, ['role' => $role]);

        return $user;
    }

    public function remove(User $user, string $role, ?User $actor = null): User
    {
        $this->guardRole($role);
        $this->roles->remove($user, $role);
        $this->audit->record($actor, AuditAction::ROLE_REMOVED, $user, ['role' => $role]);

        return $user;
    }

    /**
     * @param  list<string>  $roles
     */
    public function sync(User $user, array $roles, ?User $actor = null): User
    {
        foreach ($roles as $role) {
            $this->ensureRole($role);
        }

        $before = $this->roles->roleNamesOf($user);
        $this->roles->sync($user, $roles);
        $this->audit->record($actor, AuditAction::ROLES_SYNCED, $user, ['from' => $before, 'to' => array_values($roles)]);

        return $user;
    }

    /**
     * @return list<string>
     */
    public function rolesOf(User $user): array
    {
        return $this->roles->roleNamesOf($user);
    }

    private function guardRole(string $role): void
    {
        if (! Roles::isValid($role)) {
            throw new InvalidArgumentException(sprintf('Unknown role "%s".', $role));
        }
    }
}
