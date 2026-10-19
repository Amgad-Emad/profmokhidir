<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * @template TModel of Model
 *
 * @implements BaseRepositoryInterface<TModel>
 */
abstract class BaseEloquentRepository implements BaseRepositoryInterface
{
    /**
     * Column used to order published listings when the model has one.
     */
    protected string $orderColumn = 'sort_order';

    /**
     * @param  TModel  $model
     */
    public function __construct(protected Model $model) {}

    /**
     * @return Builder<TModel>
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function find(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->query()->latest($this->model->getKeyName())->paginate($perPage, $columns);
    }

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    public function update(Model|int $model, array $attributes): Model
    {
        $model = $this->resolve($model);
        $model->fill($attributes)->save();

        return $model->refresh();
    }

    public function delete(Model|int $model): bool
    {
        return (bool) $this->resolve($model)->delete();
    }

    public function published(): Collection
    {
        return $this->publishedQuery()->get();
    }

    public function paginatePublished(int $perPage = 15): LengthAwarePaginator
    {
        return $this->publishedQuery()->paginate($perPage);
    }

    public function findPublished(int $id): ?Model
    {
        return $this->publishedQuery()->find($id);
    }

    /**
     * Published records ordered by display order (when the column exists), then newest first.
     *
     * @return Builder<TModel>
     */
    protected function publishedQuery(): Builder
    {
        if (! $this->model->hasNamedScope('published')) {
            throw new LogicException(sprintf(
                '%s has no `published` scope; it cannot be used with the published repository helpers.',
                $this->model::class,
            ));
        }

        $query = $this->query()->published();

        if ($this->model->hasNamedScope('ordered')) {
            return $query->ordered();
        }

        return $query
            ->orderBy($this->orderColumn)
            ->latest($this->model->getKeyName());
    }

    /**
     * @param  TModel|int  $model
     * @return TModel
     */
    protected function resolve(Model|int $model): Model
    {
        return $model instanceof Model ? $model : $this->findOrFail($model);
    }
}
