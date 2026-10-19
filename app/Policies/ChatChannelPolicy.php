<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\ChatChannel;
use App\Models\User;

/**
 * Reading a channel needs chat access (approved members; admins moderate, so
 * they may read too). Managing channels needs "moderate chat".
 */
class ChatChannelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessChat() || $user->can(Permissions::MODERATE_CHAT);
    }

    public function view(User $user, ChatChannel $channel): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MODERATE_CHAT);
    }

    public function update(User $user, ChatChannel $channel): bool
    {
        return $user->can(Permissions::MODERATE_CHAT);
    }

    public function delete(User $user, ChatChannel $channel): bool
    {
        return $user->can(Permissions::MODERATE_CHAT);
    }
}
