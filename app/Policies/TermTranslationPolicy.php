<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\TermTranslation;
use App\Models\User;

/**
 * The Arabic names of grouping terms are content: admins and editors ("manage content").
 */
class TermTranslationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function update(User $user, ?TermTranslation $translation = null): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }
}
