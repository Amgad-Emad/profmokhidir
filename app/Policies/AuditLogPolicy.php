<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\AuditLog;
use App\Models\User;

/**
 * The audit log is append-only: staff may read it on the dashboard, nobody
 * may edit or delete entries.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::ACCESS_ADMIN);
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $user->can(Permissions::ACCESS_ADMIN);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $log): bool
    {
        return false;
    }
}
