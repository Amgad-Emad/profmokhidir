<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\ChatMessage;
use App\Models\User;

/**
 * Only approved members post. Authors may edit and delete their own messages;
 * moderators ("moderate chat") may delete anyone's.
 */
class ChatMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessChat() || $user->can(Permissions::MODERATE_CHAT);
    }

    public function view(User $user, ChatMessage $message): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->canAccessChat();
    }

    public function update(User $user, ChatMessage $message): bool
    {
        return $user->canAccessChat() && $message->user_id === $user->getKey();
    }

    public function delete(User $user, ChatMessage $message): bool
    {
        if ($user->can(Permissions::MODERATE_CHAT)) {
            return true;
        }

        return $user->canAccessChat() && $message->user_id === $user->getKey();
    }

    public function report(User $user, ChatMessage $message): bool
    {
        return $user->canAccessChat() && $message->user_id !== $user->getKey();
    }
}
