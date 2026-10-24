<?php

declare(strict_types=1);

namespace App\Policies;

use App\Constants\Permissions;
use App\Models\User;

/**
 * The analytics report and its exports ("view analytics": admins and editors).
 * The figures are read-only; nobody edits a rollup.
 */
class AnalyticsDailyTotalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::VIEW_ANALYTICS);
    }

    public function export(User $user): bool
    {
        return $user->can(Permissions::VIEW_ANALYTICS);
    }
}
