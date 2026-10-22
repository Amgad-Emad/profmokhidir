<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\ContentStatus;
use App\Models\User;
use App\Repositories\Contracts\ContentRepositoryInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared behaviour for draft/published content: CRUD, publish/unpublish,
 * trash and restore, reordering, cached public listings and audit logging of
 * every create, publish, unpublish, delete and restore.
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
    public function search(?string $term = null, array $filters = [], int $perPage = 20, ?string $sortBy = null, string $sortDirection = ContentRepositoryInterface::SORT_ASC): LengthAwarePaginator
    {
        return $this->repository->search($term, $filters, $perPage, $sortBy, $sortDirection);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return $this->repository->sortableColumns();
    }

    /**
     * @return list<int|string>
     */
    public function distinct(string $column): array
    {
        return $this->repository->distinct($column);
    }

    /**
     * @return list<string>
     */
    public function filterColumns(): array
    {
        return $this->repository->filterColumns();
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
     * Records still holding the seeders' placeholder copy (see Placeholders::MARKER).
     */
    public function countContaining(string $needle): int
    {
        return $this->repository->countContaining($needle);
    }

    /**
     * @return Collection<int, TModel>
     */
    public function staleDrafts(DateTimeInterface $before, int $limit = 5): Collection
    {
        return $this->repository->staleDrafts($before, $limit);
    }

    public function countStaleDrafts(DateTimeInterface $before): int
    {
        return $this->repository->countStaleDrafts($before);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, TModel>
     */
    public function findMany(array $ids): Collection
    {
        return $this->repository->findMany($ids);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(array $data, ?User $actor = null): Model
    {
        $data = $this->prepare($this->normaliseStatus($data), null);
        $data['status'] ??= ContentStatus::default();
        $data['sort_order'] ??= $this->repository->nextSortOrder();

        if ($data['status'] === ContentStatus::Published && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $model = $this->repository->create($data);

        $this->audit->record($actor, AuditAction::CONTENT_CREATED, $model, $this->auditMeta($model));

        if ($model->getAttribute('status') === ContentStatus::Published) {
            $this->audit->record($actor, AuditAction::CONTENT_PUBLISHED, $model, $this->auditMeta($model));
        }

        $this->flushCache();

        return $model;
    }

    /**
     * Save edits. A status change made through the form is audited like an
     * explicit publish or unpublish, and publishing is refused while the
     * content is not ready (see assertPublishable()).
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function update(Model $model, array $data, ?User $actor = null): Model
    {
        $data = $this->prepare($this->normaliseStatus($data), $model);
        $wasPublished = $model->getAttribute('status') === ContentStatus::Published;
        $becomesPublished = ($data['status'] ?? null) === ContentStatus::Published;

        if ($becomesPublished && ! $wasPublished) {
            $this->assertPublishable($model);
        }

        if ($becomesPublished && empty($data['published_at']) && $model->published_at === null) {
            $data['published_at'] = now();
        }

        $model = $this->repository->update($model, $data);

        if (array_key_exists('status', $data) && $becomesPublished !== $wasPublished) {
            $this->audit->record(
                $actor,
                $becomesPublished ? AuditAction::CONTENT_PUBLISHED : AuditAction::CONTENT_UNPUBLISHED,
                $model,
                $this->auditMeta($model),
            );
        }

        $this->flushCache();

        return $model;
    }

    /**
     * Move to the trash (soft delete).
     *
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
     * @return LengthAwarePaginator<int, TModel>
     */
    public function trashed(?string $term = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->trashed($term, $perPage);
    }

    public function countTrashed(): int
    {
        return $this->repository->countTrashed();
    }

    /**
     * @return TModel
     */
    public function restore(int $id, ?User $actor = null): Model
    {
        $model = $this->repository->restore($id);

        $this->audit->record($actor, AuditAction::CONTENT_RESTORED, $model, $this->auditMeta($model));
        $this->flushCache();

        return $model;
    }

    /**
     * @param  TModel  $model
     * @return TModel
     */
    public function publish(Model $model, ?User $actor = null): Model
    {
        $this->assertPublishable($model);

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
     * Persist a complete new display order.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->repository->reorder(array_map('intval', $orderedIds));
        $this->flushCache();
    }

    /**
     * Reorder a subset (one page of the index) without disturbing the rest:
     * the subset's ids take the positions they jointly occupied, in the new order.
     *
     * @param  list<int>  $orderedSubset
     */
    public function reorderWithin(array $orderedSubset): void
    {
        $subset = array_map('intval', $orderedSubset);
        $all = $this->repository->orderedIds();
        $positions = array_keys(array_intersect($all, $subset));
        $queue = array_values(array_filter($subset, fn (int $id): bool => in_array($id, $all, true)));

        foreach ($positions as $index => $position) {
            $all[$position] = $queue[$index];
        }

        $this->repository->reorder($all);
        $this->flushCache();
    }

    /**
     * Move one record up (negative offset) or down (positive) in display order.
     * The keyboard-accessible alternative to drag and drop.
     *
     * @param  TModel  $model
     */
    public function move(Model $model, int $offset): void
    {
        $all = $this->repository->orderedIds();
        $from = array_search((int) $model->getKey(), $all, true);

        if ($from === false || $offset === 0) {
            return;
        }

        $to = max(0, min(count($all) - 1, $from + $offset));

        if ($to === $from) {
            return;
        }

        [$id] = array_splice($all, $from, 1);
        array_splice($all, $to, 0, [$id]);

        $this->repository->reorder($all);
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
     * Hook for subclasses: throw (e.g. ImagesNotReadyException) when the record
     * may not go public yet. Runs before publish() and before an update that
     * switches a draft to published.
     *
     * @param  TModel  $model
     */
    protected function assertPublishable(Model $model): void {}

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
