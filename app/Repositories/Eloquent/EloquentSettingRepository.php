<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentSettingRepository implements SettingRepositoryInterface
{
    public function __construct(private readonly Setting $model) {}

    public function all(): Collection
    {
        return $this->model->newQuery()->orderBy('key')->get();
    }

    public function find(string $key): ?Setting
    {
        return $this->model->newQuery()->where('key', $key)->first();
    }

    public function values(): array
    {
        return $this->model->newQuery()->orderBy('key')->pluck('value', 'key')->all();
    }

    public function put(string $key, ?string $value): Setting
    {
        return $this->model->newQuery()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->put((string) $key, $value);
        }
    }

    public function forget(string $key): bool
    {
        return $this->model->newQuery()->where('key', $key)->delete() > 0;
    }
}
