<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\ContentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @template TModel of Model
 *
 * @extends BaseEloquentRepository<TModel>
 *
 * @implements ContentRepositoryInterface<TModel>
 */
abstract class BaseContentRepository extends BaseEloquentRepository implements ContentRepositoryInterface
{
    /**
     * Columns matched by search(). The fulltext indexes in the migrations cover
     * the same columns; LIKE is used here so results are visible inside the
     * request's own transaction and for terms shorter than the fulltext minimum.
     *
     * @var list<string>
     */
    protected array $searchColumns = ['title'];

    /**
     * Columns search() accepts as exact-match filters.
     *
     * @var list<string>
     */
    protected array $filterColumns = ['status'];

    /**
     * Relations eager-loaded on public listings.
     *
     * @var list<string>
     */
    protected array $publishedWith = [];

    public function search(?string $term = null, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $term = trim((string) $term);

        return $this->query()
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $query) use ($term): void {
                    foreach ($this->searchColumns as $column) {
                        $query->orWhere($column, 'like', '%'.$this->escapeLike($term).'%');
                    }
                });
            })
            ->when($filters !== [], function (Builder $query) use ($filters): void {
                foreach ($filters as $column => $value) {
                    if ($value === null || $value === '' || ! in_array($column, $this->filterColumns, true)) {
                        continue;
                    }

                    $query->where($column, $value instanceof \BackedEnum ? $value->value : $value);
                }
            })
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach (array_values($orderedIds) as $position => $id) {
                $this->query()->whereKey((int) $id)->update(['sort_order' => $position + 1]);
            }
        });
    }

    public function nextSortOrder(): int
    {
        return ((int) $this->query()->max('sort_order')) + 1;
    }

    public function countPublished(): int
    {
        return $this->query()->published()->count();
    }

    protected function publishedQuery(): Builder
    {
        return parent::publishedQuery()->with($this->publishedWith);
    }

    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
