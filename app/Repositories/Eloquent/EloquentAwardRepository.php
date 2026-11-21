<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Award;
use App\Repositories\Contracts\AwardRepositoryInterface;

/**
 * @extends BaseContentRepository<Award>
 */
class EloquentAwardRepository extends BaseContentRepository implements AwardRepositoryInterface
{
    protected array $searchColumns = ['name', 'organisation', 'description'];

    protected array $sortableColumns = ['name', 'year', 'organisation'];

    protected array $filterColumns = ['status', 'year'];

    /** The roll of honour shows each award's photographs. */
    protected array $publishedWith = ['media'];

    public function __construct(Award $award)
    {
        parent::__construct($award);
    }
}
