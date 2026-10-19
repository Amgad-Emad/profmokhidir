<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\ChatReport;
use App\Models\User;

/**
 * Members file reports; only "moderate chat" (admins) may list and resolve them.
 */
class ChatReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MODERATE_CHAT);
    }

    public function view(User $user, ChatReport $report): bool
    {
        return $user->can(Permissions::MODERATE_CHAT) || $report->reporter_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->canAccessChat();
    }

    public function resolve(User $user, ChatReport $report): bool
    {
        return $user->can(Permissions::MODERATE_CHAT);
    }

    public function delete(User $user, ChatReport $report): bool
    {
        return $user->can(Permissions::MODERATE_CHAT);
    }
}
