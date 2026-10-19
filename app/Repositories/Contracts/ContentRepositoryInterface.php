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
    public const string SORT_ASC = 'asc';

    public const string SORT_DESC = 'desc';

    /**
     * Admin listing: optional free-text term over the type's search columns,
     * optional exact-match column filters (e.g. status, type, year), sorted by
     * one of sortableColumns() or, with no sort, in display order.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TModel>
     */
    public function search(?string $term = null, array $filters = [], int $perPage = 20, ?string $sortBy = null, string $sortDirection = self::SORT_ASC): LengthAwarePaginator;

    /**
     * Columns search() accepts as sort keys.
     *
     * @return list<string>
     */
    public function sortableColumns(): array;

    /**
     * Columns search() accepts as exact-match filters.
     *
     * @return list<string>
     */
    public function filterColumns(): array;

    /**
     * Soft-deleted records, newest deletion first.
     *
     * @return LengthAwarePaginator<int, TModel>
     */
    public function trashed(?string $term = null, int $perPage = 20): LengthAwarePaginator;

    /**
     * @return TModel|null
     */
    public function findTrashed(int $id): ?Model;

    /**
     * @return TModel
     */
    public function restore(int $id): Model;

    public function countTrashed(): int;

    /**
     * Persist a new display order; ids are listed in their new order.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;

    /**
     * Every (non-trashed) id in display order.
     *
     * @return list<int>
     */
    public function orderedIds(): array;

    /**
     * Distinct non-empty values of a column (for filter options), newest/highest first.
     *
     * @return list<int|string>
     */
    public function distinct(string $column): array;

    public function nextSortOrder(): int;

    public function countPublished(): int;
}
