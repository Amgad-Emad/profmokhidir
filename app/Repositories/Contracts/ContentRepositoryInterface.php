<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared admin helpers for every draft/published content type.
 *
 * @template TModel of Model
 *
 * @extends BaseRepositoryInterface<TModel>
 */
interface ContentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Admin listing: optional free-text term over the type's search columns,
     * optional exact-match column filters (e.g. status, type, year), ordered for editing.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TModel>
     */
    public function search(?string $term = null, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Persist a new display order; ids are listed in their new order.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;

    public function nextSortOrder(): int;

    public function countPublished(): int;
}
