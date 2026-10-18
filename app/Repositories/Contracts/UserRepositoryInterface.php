<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<User>
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * @return Collection<int, User>
     */
    public function withRole(string $role): Collection;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateByRole(?string $role = null, int $perPage = 15): LengthAwarePaginator;

    public function countByRole(string $role): int;
}
