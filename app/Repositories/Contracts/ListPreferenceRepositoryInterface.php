<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\ListDensity;
use App\Models\ListPreference;
use App\Models\User;

/**
 * Each user's last-used view and row density of each admin list.
 */
interface ListPreferenceRepositoryInterface
{
    public function find(User $user, string $screen): ?ListPreference;

    /**
     * Store the screen's state (null keeps the stored one) and density (null keeps it).
     *
     * @param  array<string, mixed>|null  $state
     */
    public function save(User $user, string $screen, ?array $state, ?ListDensity $density = null): ListPreference;

    public function forget(User $user, string $screen): void;
}
