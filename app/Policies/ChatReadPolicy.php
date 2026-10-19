<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\ChatRead;
use App\Models\User;

/**
 * Read cursors belong to their user; moderators may inspect them.
 */
class ChatReadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MODERATE_CHAT);
    }

    public function view(User $user, ChatRead $read): bool
    {
        return $read->user_id === $user->getKey() || $user->can(Permissions::MODERATE_CHAT);
    }

    public function update(User $user, ChatRead $read): bool
    {
        return $read->user_id === $user->getKey() && $user->canAccessChat();
    }
}
