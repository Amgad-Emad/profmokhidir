<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared rules for every draft/published content type: anyone holding the
 * "manage content" permission (editors and admins) may list, view, create,
 * edit, publish, reorder and delete. Policies check permissions, never roles.
 */
abstract class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    /** Delete a trashed record for good. */
    public function forceDelete(User $user, Model $model): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function publish(User $user, Model $model): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function reorder(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function manageMedia(User $user, Model $model): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }
}
