<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\HomeQuote;
use App\Repositories\Contracts\HomeQuoteRepositoryInterface;

/**
 * @extends BaseContentRepository<HomeQuote>
 */
class EloquentHomeQuoteRepository extends BaseContentRepository implements HomeQuoteRepositoryInterface
{
    protected array $searchColumns = ['quote', 'attribution', 'source'];

    protected array $sortableColumns = ['attribution'];

    protected array $filterColumns = ['status'];

    public function __construct(HomeQuote $quote)
    {
        parent::__construct($quote);
    }
}
