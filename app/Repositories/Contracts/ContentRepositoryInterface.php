<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
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

    /** Pseudo filter for search(): true keeps records still holding Placeholders::MARKER. */
    public const string FILTER_PLACEHOLDER = 'placeholder';

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

    /**
     * Delete a trashed record for good (its media goes with it).
     */
    public function forceDelete(int $id): void;

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

    /**
     * Records (trashed ones excluded) whose text still contains the given marker
     * in any searchable or placeholder column.
     */
    public function countContaining(string $needle): int;

    /**
     * Drafts last changed before the given moment, oldest first.
     *
     * @return Collection<int, TModel>
     */
    public function staleDrafts(DateTimeInterface $before, int $limit = 5): Collection;

    public function countStaleDrafts(DateTimeInterface $before): int;

    /**
     * Records whose ids are given (trashed ones excluded), in display order.
     *
     * @param  list<int>  $ids
     * @return Collection<int, TModel>
     */
    public function findMany(array $ids): Collection;
}
