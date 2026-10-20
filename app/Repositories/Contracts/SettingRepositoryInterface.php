<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

interface SettingRepositoryInterface
{
    /**
     * Every stored setting.
     *
     * @return Collection<int, Setting>
     */
    public function all(): Collection;

    public function find(string $key): ?Setting;

    /**
     * Stored values keyed by setting key (null for a stored empty value).
     *
     * @return array<string, string|null>
     */
    public function values(): array;

    /**
     * Create or update one setting.
     */
    public function put(string $key, ?string $value): Setting;

    /**
     * Create or update several settings at once.
     *
     * @param  array<string, string|null>  $values
     */
    public function putMany(array $values): void;

    public function forget(string $key): bool;
}
