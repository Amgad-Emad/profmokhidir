<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\Tribute;
use App\Models\User;

/**
 * Tributes are submitted by visitors and moderated by anyone holding
 * "moderate tributes" (editors and admins).
 */
class TributePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MODERATE_TRIBUTES);
    }

    public function view(User $user, Tribute $tribute): bool
    {
        return $user->can(Permissions::MODERATE_TRIBUTES);
    }

    /** Staff never author tributes; they only moderate them. */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Tribute $tribute): bool
    {
        return $user->can(Permissions::MODERATE_TRIBUTES);
    }

    public function approve(User $user, Tribute $tribute): bool
    {
        return $user->can(Permissions::MODERATE_TRIBUTES);
    }

    public function reject(User $user, Tribute $tribute): bool
    {
        return $user->can(Permissions::MODERATE_TRIBUTES);
    }

    public function delete(User $user, Tribute $tribute): bool
    {
        return $user->can(Permissions::MODERATE_TRIBUTES);
    }

    public function reorder(User $user): bool
    {
        return $user->can(Permissions::MODERATE_TRIBUTES);
    }
}
