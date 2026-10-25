<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\Page;
use App\Models\User;

/**
 * Page copy (Admin › Pages): editors and admins, through the "manage content"
 * permission, may list the pages, edit their words and images, publish and
 * unpublish them. Pages are fixed by the design, so none can be created or
 * deleted. Policies check permissions, never roles.
 */
class PagePolicy
{
    /** The model this policy guards. */
    public const string MODEL = Page::class;

    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function view(User $user, Page $page): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Page $page): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function publish(User $user, Page $page): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function manageMedia(User $user, Page $page): bool
    {
        return $user->can(Permissions::MANAGE_CONTENT);
    }

    public function delete(User $user, Page $page): bool
    {
        return false;
    }
}
