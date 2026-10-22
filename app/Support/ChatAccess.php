<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\Permissions;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * The two access questions every chat service asks, answered from the
 * member's current standing on every call so a suspension or removal takes
 * effect on the very next poll, send or pin.
 */
final class ChatAccess
{
    /**
     * @throws AuthorizationException
     */
    public static function guard(User $user): void
    {
        $user->unsetRelation('roles');

        if (! $user->canAccessChat()) {
            throw new AuthorizationException('The community chat is only open to approved members.');
        }
    }

    public static function canModerate(User $user): bool
    {
        return $user->isAdmin() || $user->can(Permissions::MODERATE_CHAT);
    }

    /**
     * @throws AuthorizationException
     */
    public static function guardModerator(User $user): void
    {
        if (! self::canModerate($user)) {
            throw new AuthorizationException('Only moderators may do this.');
        }
    }
}
