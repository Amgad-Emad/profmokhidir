<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\Setting;
use App\Models\User;

/**
 * Site settings are admin-only ("manage settings").
 */
class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::MANAGE_SETTINGS);
    }

    public function update(User $user, ?Setting $setting = null): bool
    {
        return $user->can(Permissions::MANAGE_SETTINGS);
    }
}
