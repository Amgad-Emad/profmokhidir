<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\ContentStatus;
use App\Models\User;
use App\Repositories\Contracts\ContentRepositoryInterface;
use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared behaviour for draft/published content: CRUD, publish/unpublish,
 * reordering, cached public listings and audit logging of deletions.
 *
 * @template TModel of Model
 */
abstract class AbstractContentService
{
    /**
     * @param  ContentRepositoryInterface<TModel>  $repository
     */
    public function __construct(
        protected readonly ContentRepositoryInterface $repository,
        protected readonly AuditLogService $audit,
        protected readonly CacheRepository $cache,
    ) {}

    /**
     * Cache key (CacheKeys) for this type's public listing.
     */
    abstract protected function cacheKey(): string;

    /**
     * @return TModel|null
     */
    public function find(int $id): ?Model
    {
        return $this->repository->find($id);
    }

    /**
     * @return TModel
     */
    public function findOrFail(int $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TModel>
     */
    public function search(?string $term = null, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->search($term, $filters, $perPage);
    }

    /**
     * Published records in display order, cached for the public site.
     *
     * @return Collection<int, TModel>
     */
    public function published(): Collection
    {
        return $this->remember($this->cacheKey(), fn (): Collection => $this->repository->published());
    }

    public function countPublished(): int
    {
        return $this->repository->countPublished();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(array $data): Model
    {
        $data = $this->prepare($this->normaliseStatus($data), null);
        $data['status'] ??= ContentStatus::default();
        $data['sort_order'] ??= $this->repository->nextSortOrder();

        if ($data['status'] === ContentStatus::Published && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $model = $this->repository->create($data);
        $this->flushCache();

        return $model;
    }

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function update(Model $model, array $data): Model
    {
        $data = $this->prepare($this->normaliseStatus($data), $model);

        if (($data['status'] ?? null) === ContentStatus::Published && empty($data['published_at']) && $model->published_at === null) {
            $data['published_at'] = now();
        }

        $model = $this->repository->update($model, $data);
        $this->flushCache();

        return $model;
    }

    /**
     * @param  TModel  $model
     */
    public function delete(Model $model, ?User $actor = null): bool
    {
        $this->audit->record($actor, AuditAction::CONTENT_DELETED, $model, $this->auditMeta($model));

        $deleted = $this->repository->delete($model);
        $this->flushCache();

        return $deleted;
    }

    /**
     * @param  TModel  $model
     * @return TModel
     */
    public function publish(Model $model, ?User $actor = null): Model
    {
        $model = $this->repository->update($model, [
            'status' => ContentStatus::Published,
            'published_at' => $model->published_at ?? now(),
        ]);

        $this->audit->record($actor, AuditAction::CONTENT_PUBLISHED, $model, $this->auditMeta($model));
        $this->flushCache();

        return $model;
    }

    /**
     * @param  TModel  $model
     * @return TModel
     */
    public function unpublish(Model $model, ?User $actor = null): Model
    {
        $model = $this->repository->update($model, ['status' => ContentStatus::Draft]);

        $this->audit->record($actor, AuditAction::CONTENT_UNPUBLISHED, $model, $this->auditMeta($model));
        $this->flushCache();

        return $model;
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->repository->reorder(array_map('intval', $orderedIds));
        $this->flushCache();
    }

    /**
     * Hook for subclasses (slugs, derived fields) before create/update.
     *
     * @param  array<string, mixed>  $data
     * @param  TModel|null  $existing
     * @return array<string, mixed>
     */
    protected function prepare(array $data, ?Model $existing): array
    {
        return $data;
    }

    /**
     * Cache keys invalidated whenever this type changes.
     *
     * @return list<string>
     */
    protected function cacheKeys(): array
    {
        return [$this->cacheKey()];
    }

    public function flushCache(): void
    {
        foreach ($this->cacheKeys() as $key) {
            $this->cache->forget($key);
        }
    }

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    protected function remember(string $key, Closure $callback): mixed
    {
        return $this->cache->remember($key, CacheKeys::TTL_SECONDS, $callback);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normaliseStatus(array $data): array
    {
        if (isset($data['status']) && is_string($data['status'])) {
            $data['status'] = ContentStatus::from($data['status']);
        }

        return $data;
    }

    /**
     * @param  TModel  $model
     * @return array<string, mixed>
     */
    protected function auditMeta(Model $model): array
    {
        $label = $model->getAttribute('title') ?? $model->getAttribute('name');

        return array_filter(['label' => $label], fn ($value): bool => $value !== null);
    }
}
