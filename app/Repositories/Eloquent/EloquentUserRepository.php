<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseEloquentRepository<User>
 */
class EloquentUserRepository extends BaseEloquentRepository implements UserRepositoryInterface
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', mb_strtolower(trim($email)))->first();
    }

    public function withRole(string $role): Collection
    {
        return $this->query()->role($role)->orderBy('name')->get();
    }

    public function paginateByRole(?string $role = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->when($role !== null, fn ($query) => $query->role($role))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function countByRole(string $role): int
    {
        return $this->query()->role($role)->count();
    }
}
