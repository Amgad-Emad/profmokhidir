<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\TributeStatus;
use App\Models\Tribute;
use App\Repositories\Contracts\TributeRepositoryInterface;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseContentRepository<Tribute>
 */
class EloquentTributeRepository extends BaseContentRepository implements TributeRepositoryInterface
{
    protected array $searchColumns = ['name', 'message', 'location'];

    protected array $filterColumns = ['status', 'relationship'];

    public function __construct(Tribute $tribute)
    {
        parent::__construct($tribute);
    }

    public function withStatus(TributeStatus $status): Collection
    {
        return $this->query()->withStatus($status)->ordered()->get();
    }

    public function paginateByStatus(?TributeStatus $status = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->when($status !== null, fn ($query) => $query->withStatus($status))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countByStatus(TributeStatus $status): int
    {
        return $this->query()->withStatus($status)->count();
    }

    public function latestApproved(int $limit = 3): Collection
    {
        return $this->query()->published()->orderByDesc('approved_at')->orderByDesc('id')->limit($limit)->get();
    }

    public function countRecentFromIp(string $ipHash, DateTimeInterface $since): int
    {
        return $this->query()->where('ip_hash', $ipHash)->where('created_at', '>=', $since)->count();
    }
}
