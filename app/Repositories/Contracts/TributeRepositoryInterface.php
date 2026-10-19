<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\TributeStatus;
use App\Models\Tribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends ContentRepositoryInterface<Tribute>
 */
interface TributeRepositoryInterface extends ContentRepositoryInterface
{
    /**
     * @return Collection<int, Tribute>
     */
    public function withStatus(TributeStatus $status): Collection;

    /**
     * @return LengthAwarePaginator<int, Tribute>
     */
    public function paginateByStatus(?TributeStatus $status = null, int $perPage = 20): LengthAwarePaginator;

    public function countByStatus(TributeStatus $status): int;

    /**
     * @return Collection<int, Tribute>
     */
    public function latestApproved(int $limit = 3): Collection;

    /**
     * Number of tributes submitted from the given hashed IP since the given time (rate limiting).
     */
    public function countRecentFromIp(string $ipHash, \DateTimeInterface $since): int;
}
