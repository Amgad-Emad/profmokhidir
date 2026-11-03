<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\ContentStatus;
use App\Constants\Locales;
use App\Models\User;
use App\Repositories\Contracts\ContentRepositoryInterface;
use App\Repositories\Contracts\RevisionRepositoryInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\MediaLibrary\HasMedia;

/**
 * Shared behaviour for draft/published content: CRUD, publish/unpublish,
 * trash and restore, reordering, cached public listings and audit logging of
 * every create, publish, unpublish, delete and restore.
 *
 * @template TModel of Model
 */
abstract class AbstractContentService
{
    /** Columns a duplicate never takes from its original (it gets its own). */
    private const array NOT_DUPLICATED = ['slug', 'status', 'published_at', 'sort_order', 'is_featured', 'search_text'];

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
     * @param  array<string, mixed>  $filters
     */
    public function count(?string $term = null, array $filters = []): int
    {
        return $this->repository->count($term, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function countBy(string $column, ?string $term = null, array $filters = []): array
    {
        return $this->repository->countBy($column, $term, $filters);
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
     * Records with a column filled in English but not yet in the language (Arabic by default).
     */
    public function countNeedingTranslation(string $locale = Locales::AR): int
    {
        return $this->repository->countNeedingTranslation($locale);
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
     * A draft copy of a record: its words in every language (the title marked
     * as a copy), its other details and its files, at the end of the display
     * order with a fresh address. The copy is audited as created.
     *
     * @param  TModel  $model
     * @return TModel
     */
    public function duplicate(Model $model, ?User $actor = null): Model
    {
        $copy = $this->create($this->duplicateData($model), $actor);

        if ($model instanceof HasMedia && $copy instanceof HasMedia) {
            app(MediaService::class)->copyAll($model, $copy);
        }

        return $copy;
    }

    /**
     * The fillable details of a record for its copy (translatable ones as locale maps).
     *
     * @param  TModel  $model
     * @return array<string, mixed>
     */
    protected function duplicateData(Model $model): array
    {
        $data = [];
        $translatable = method_exists($model, 'isTranslatableAttribute');

        foreach ($model->getFillable() as $key) {
            if (in_array($key, self::NOT_DUPLICATED, true) || ! array_key_exists($key, $model->getAttributes())) {
                continue;
            }

            $data[$key] = $translatable && $model->isTranslatableAttribute($key) ? $model->getTranslations($key) : $model->getAttribute($key);
        }

        foreach (['title', 'name', 'quote'] as $label) {
            if (! isset($data[$label])) {
                continue;
            }

            $data[$label] = is_array($data[$label])
                ? collect($data[$label])->map(fn (mixed $value, string $locale): mixed => is_string($value) && $value !== '' ? __(':title (copy)', ['title' => $value], $locale) : $value)->all()
                : __(':title (copy)', ['title' => (string) $data[$label]], Locales::DEFAULT);

            break;
        }

        $data['status'] = ContentStatus::Draft;

        return $data;
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
     * Delete a record from the trash for good, with its media and its revisions. Only
     * trashed records can be purged: a live record goes to the trash first.
     */
    public function forceDelete(int $id, ?User $actor = null): void
    {
        $model = $this->repository->findTrashed($id) ?? throw new ModelNotFoundException("No record [{$id}] in the trash.");

        $this->audit->record($actor, AuditAction::CONTENT_PURGED, $model, $this->auditMeta($model));
        app(RevisionRepositoryInterface::class)->forget($model);
        $this->repository->forceDelete($id);
        $this->flushCache();
    }

    /**
     * Find a trashed record (for the permanent delete dialog).
     *
     * @return TModel|null
     */
    public function findTrashed(int $id): ?Model
    {
        return $this->repository->findTrashed($id);
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
     * Apply a clean-up to a value, or to each language of a locale map
     * (['en' => , 'ar' => ], as the bilingual edit screens send them).
     *
     * @param  Closure(mixed): mixed  $callback
     */
    protected function eachLocale(mixed $value, Closure $callback): mixed
    {
        return Locales::isLocaleMap($value) ? array_map($callback, $value) : $callback($value);
    }

    /**
     * The English text of a value that may be a locale map.
     */
    protected function english(mixed $value): mixed
    {
        return Locales::isLocaleMap($value) ? ($value[Locales::DEFAULT] ?? null) : $value;
    }

    /**
     * withUniqueSlug() (GeneratesSlugs) fed with the English title, so the
     * address is always derived from English whatever language is edited.
     *
     * @param  array<string, mixed>  $data
     * @param  TModel|null  $existing
     * @return array<string, mixed>
     */
    protected function withEnglishSlug(array $data, ?Model $existing, string $source = 'title'): array
    {
        $copy = $data;

        if (array_key_exists($source, $copy)) {
            $copy[$source] = $this->english($copy[$source]);
        }

        $slugged = $this->withUniqueSlug($copy, $existing, $source);

        if (array_key_exists('slug', $slugged)) {
            $data['slug'] = $slugged['slug'];
        }

        return $data;
    }

    /**
     * @param  TModel  $model
     * @return array<string, mixed>
     */
    protected function auditMeta(Model $model): array
    {
        $label = null;

        foreach (['title', 'name'] as $column) {
            $label ??= method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute($column)
                ? ($model->getTranslation($column, Locales::DEFAULT, false) ?: null)
                : $model->getAttribute($column);
        }

        return array_filter(['label' => $label], fn ($value): bool => $value !== null);
    }
}
