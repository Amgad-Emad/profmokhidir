<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\ListDensity;
use App\Models\ListPreference;
use App\Models\User;
use App\Repositories\Contracts\ListPreferenceRepositoryInterface;

class EloquentListPreferenceRepository implements ListPreferenceRepositoryInterface
{
    public function __construct(private readonly ListPreference $model) {}

    public function find(User $user, string $screen): ?ListPreference
    {
        return $this->model->newQuery()->where('user_id', $user->getKey())->where('screen', $screen)->first();
    }

    public function save(User $user, string $screen, ?array $state, ?ListDensity $density = null): ListPreference
    {
        $preference = $this->find($user, $screen) ?? $this->model->newInstance(['user_id' => $user->getKey(), 'screen' => $screen]);

        if ($state !== null) {
            $preference->state = $state;
        }

        if ($density !== null) {
            $preference->density = $density;
        }

        if ($preference->isDirty() || ! $preference->exists) {
            $preference->save();
        }

        return $preference;
    }

    public function saveTable(User $user, string $screen, ?array $columns, ?int $perPage = null): ListPreference
    {
        $preference = $this->find($user, $screen) ?? $this->model->newInstance(['user_id' => $user->getKey(), 'screen' => $screen]);

        if ($columns !== null) {
            $preference->columns = $columns;
        }

        if ($perPage !== null) {
            $preference->per_page = $perPage;
        }

        if ($preference->isDirty() || ! $preference->exists) {
            $preference->save();
        }

        return $preference;
    }

    public function forget(User $user, string $screen): void
    {
        $this->model->newQuery()->where('user_id', $user->getKey())->where('screen', $screen)->delete();
    }
}
