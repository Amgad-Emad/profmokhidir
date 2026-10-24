<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\User;

/**
 * Accounts and roles need "manage users"; community applications need
 * "manage community". Both are admin-only permissions. Nobody may delete,
 * deactivate or change the roles of their own account (UserService also
 * protects the last active administrator).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MANAGE_USERS) || $user->can(Permissions::MANAGE_COMMUNITY);
    }

    public function view(User $user, User $subject): bool
    {
        return $user->is($subject) || $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_USERS);
    }

    public function update(User $user, User $subject): bool
    {
        return $user->is($subject) || $user->can(Permissions::MANAGE_USERS);
    }

    public function delete(User $user, User $subject): bool
    {
        return ! $user->is($subject) && $user->can(Permissions::MANAGE_USERS);
    }

    public function assignRole(User $user, User $subject): bool
    {
        return ! $user->is($subject) && $user->can(Permissions::MANAGE_USERS);
    }

    /** Invite a new administrator or editor. */
    public function invite(User $user): bool
    {
        return $user->can(Permissions::MANAGE_USERS);
    }

    public function resendInvitation(User $user, User $subject): bool
    {
        return ! $user->is($subject) && $user->can(Permissions::MANAGE_USERS);
    }

    public function deactivate(User $user, User $subject): bool
    {
        return ! $user->is($subject) && $user->can(Permissions::MANAGE_USERS);
    }

    public function reactivate(User $user, User $subject): bool
    {
        return ! $user->is($subject) && $user->can(Permissions::MANAGE_USERS);
    }

    /** Hide the dashboard's two-factor card: only for one's own account. */
    public function dismissTwoFactorReminder(User $user, User $subject): bool
    {
        return $user->is($subject);
    }

    /** Approve, reject or suspend a community application or membership. */
    public function review(User $user, User $subject): bool
    {
        return ! $user->is($subject) && $user->can(Permissions::MANAGE_COMMUNITY);
    }
}
