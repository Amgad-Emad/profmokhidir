<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\OtherItem;
use App\Models\User;
use App\Repositories\Contracts\OtherItemRepositoryInterface;
use App\Services\Concerns\GeneratesSlugs;
use App\Services\Concerns\GuardsImagePublishing;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @extends AbstractContentService<OtherItem>
 */
class OtherItemService extends AbstractContentService
{
    use GeneratesSlugs, GuardsImagePublishing;

    public function __construct(
        OtherItemRepositoryInterface $items,
        AuditLogService $audit,
        CacheRepository $cache,
        private readonly MediaService $media,
    ) {
        parent::__construct($items, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::OTHER_ITEMS;
    }

    protected function cacheKeys(): array
    {
        return [CacheKeys::OTHER_ITEMS, CacheKeys::OTHER_ITEMS.'.categories'];
    }

    public function findPublishedBySlug(string $slug): ?OtherItem
    {
        return $this->items()->findPublishedBySlug($slug);
    }

    /**
     * @return Collection<int, OtherItem>
     */
    public function publishedInCategory(string $category): Collection
    {
        return $this->items()->publishedInCategory($category);
    }

    /**
     * Published items grouped by category, categories alphabetical with
     * uncategorised items last under an empty key.
     *
     * @return array<string, Collection<int, OtherItem>>
     */
    public function publishedByCategory(): array
    {
        $grouped = $this->published()
            ->groupBy(fn (OtherItem $item): string => trim((string) $item->category))
            ->sortKeys();

        if ($grouped->has('')) {
            $uncategorised = $grouped->pull('');
            $grouped->put('', $uncategorised);
        }

        return $grouped->map(fn ($items) => $items->values())->all();
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        return $this->remember(CacheKeys::OTHER_ITEMS.'.categories', fn (): array => $this->items()->publishedCategories());
    }

    /**
     * @param  array<string, mixed>  $properties  keyed by MediaProperties
     */
    public function addMedia(OtherItem $item, string|UploadedFile $file, array $properties = []): Media
    {
        $properties[MediaProperties::TREATMENT] ??= MediaTreatment::Archive->value;

        $media = $this->media->attach($item, $file, MediaCollections::MEDIA, $properties);
        $this->flushCache();

        return $media;
    }

    public function removeMedia(Media $media, ?User $actor = null): void
    {
        $this->media->remove($media, $actor);
        $this->flushCache();
    }

    protected function guardedImageCollections(): array
    {
        return [MediaCollections::MEDIA];
    }

    protected function prepare(array $data, $existing): array
    {
        return $this->withUniqueSlug($data, $existing);
    }

    private function items(): OtherItemRepositoryInterface
    {
        /** @var OtherItemRepositoryInterface $repository */
        $repository = $this->repository;

        return $repository;
    }
}
