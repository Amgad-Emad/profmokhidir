<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\ContentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

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
     * Columns search() accepts as sort keys, besides the ones every type has.
     *
     * @var list<string>
     */
    protected array $sortableColumns = ['title'];

    /**
     * Relations eager-loaded on public listings.
     *
     * @var list<string>
     */
    protected array $publishedWith = [];

    public function search(?string $term = null, array $filters = [], int $perPage = 20, ?string $sortBy = null, string $sortDirection = self::SORT_ASC): LengthAwarePaginator
    {
        $query = $this->filtered($this->query(), $term, $filters);

        if ($sortBy !== null && $sortBy !== '') {
            if (! in_array($sortBy, $this->sortableColumns(), true)) {
                throw new InvalidArgumentException(sprintf('Cannot sort %s by "%s".', $this->model::class, $sortBy));
            }

            $query->orderBy($sortBy, $sortDirection === self::SORT_DESC ? 'desc' : 'asc')
                ->orderByDesc($this->model->getKeyName());
        } else {
            $query->ordered();
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function sortableColumns(): array
    {
        return array_values(array_unique(array_merge(
            ['sort_order', 'status', 'published_at', 'updated_at'],
            $this->sortableColumns,
        )));
    }

    public function filterColumns(): array
    {
        return $this->filterColumns;
    }

    public function trashed(?string $term = null, int $perPage = 20): LengthAwarePaginator
    {
        $this->assertSoftDeletes();

        return $this->filtered($this->query()->onlyTrashed(), $term, [])
            ->orderByDesc('deleted_at')
            ->orderByDesc($this->model->getKeyName())
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findTrashed(int $id): ?Model
    {
        $this->assertSoftDeletes();

        return $this->query()->onlyTrashed()->find($id);
    }

    public function restore(int $id): Model
    {
        $this->assertSoftDeletes();

        /** @var TModel $model */
        $model = $this->query()->onlyTrashed()->findOrFail($id);
        $model->restore();

        return $model->refresh();
    }

    public function countTrashed(): int
    {
        return $this->softDeletes() ? $this->query()->onlyTrashed()->count() : 0;
    }

    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach (array_values($orderedIds) as $position => $id) {
                $this->query()->whereKey((int) $id)->update(['sort_order' => $position + 1]);
            }
        });
    }

    public function orderedIds(): array
    {
        return $this->query()->ordered()->pluck($this->model->getQualifiedKeyName())
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function distinct(string $column): array
    {
        if (! in_array($column, $this->filterColumns, true)) {
            throw new InvalidArgumentException(sprintf('%s has no "%s" filter.', $this->model::class, $column));
        }

        return $this->query()->whereNotNull($column)->where($column, '!=', '')
            ->distinct()->orderByDesc($column)->pluck($column)
            ->map(fn ($value): int|string => $value instanceof \BackedEnum ? $value->value : (is_numeric($value) ? (int) $value : (string) $value))
            ->all();
    }

    public function nextSortOrder(): int
    {
        $query = $this->softDeletes() ? $this->query()->withTrashed() : $this->query();

        return ((int) $query->max('sort_order')) + 1;
    }

    public function countPublished(): int
    {
        return $this->query()->published()->count();
    }

    protected function publishedQuery(): Builder
    {
        return parent::publishedQuery()->with($this->publishedWith);
    }

    /**
     * @param  Builder<TModel>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    protected function filtered(Builder $query, ?string $term, array $filters): Builder
    {
        $term = trim((string) $term);

        return $query
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
            });
    }

    protected function softDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->model), true);
    }

    protected function assertSoftDeletes(): void
    {
        if (! $this->softDeletes()) {
            throw new LogicException(sprintf('%s does not use soft deletes; it has no trash.', $this->model::class));
        }
    }

    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
