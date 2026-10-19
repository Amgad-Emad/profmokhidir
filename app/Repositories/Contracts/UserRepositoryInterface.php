<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\CommunityStatus;
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
     * Users holding the given Spatie role.
     *
     * @return Collection<int, User>
     */
    public function withRole(string $role): Collection;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateByRole(?string $role = null, int $perPage = 15): LengthAwarePaginator;

    public function countByRole(string $role): int;

    /**
     * @return Collection<int, User>
     */
    public function withCommunityStatus(CommunityStatus $status): Collection;

    public function countByCommunityStatus(CommunityStatus $status): int;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateByCommunityStatus(?CommunityStatus $status = null, int $perPage = 15): LengthAwarePaginator;
}
