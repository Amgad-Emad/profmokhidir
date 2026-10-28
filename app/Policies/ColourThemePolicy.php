<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\ColourTheme;
use App\Models\User;

/**
 * Colour themes are admin-only ("manage appearance"). Suggested themes are
 * read-only: they can be applied or duplicated, never edited, renamed or deleted.
 */
class ColourThemePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MANAGE_APPEARANCE);
    }

    public function view(User $user, ColourTheme $theme): bool
    {
        return $user->can(Permissions::MANAGE_APPEARANCE);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::MANAGE_APPEARANCE);
    }

    public function update(User $user, ColourTheme $theme): bool
    {
        return $user->can(Permissions::MANAGE_APPEARANCE) && ! $theme->is_preset;
    }

    /** Make a theme (preset or custom) the one the site is drawn with. */
    public function publish(User $user, ColourTheme $theme): bool
    {
        return $user->can(Permissions::MANAGE_APPEARANCE);
    }

    public function delete(User $user, ColourTheme $theme): bool
    {
        return $user->can(Permissions::MANAGE_APPEARANCE) && ! $theme->is_preset;
    }
}
