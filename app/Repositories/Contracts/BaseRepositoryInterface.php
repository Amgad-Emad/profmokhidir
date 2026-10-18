<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface BaseRepositoryInterface
{
    /**
     * @return TModel|null
     */
    public function find(int $id): ?Model;

    /**
     * @return TModel
     */
    public function findOrFail(int $id): Model;

    /**
     * @param  list<string>  $columns
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * @param  list<string>  $columns
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * @param  TModel|int  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model|int $model, array $attributes): Model;

    /**
     * @param  TModel|int  $model
     */
    public function delete(Model|int $model): bool;

    /**
     * Published records in display order. Requires a `published` scope on the model.
     *
     * @return Collection<int, TModel>
     */
    public function published(): Collection;

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginatePublished(int $perPage = 15): LengthAwarePaginator;

    /**
     * @return TModel|null
     */
    public function findPublished(int $id): ?Model;
}
