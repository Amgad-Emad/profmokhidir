<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Constants\MediaCollections;
use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Repositories\Contracts\TeachingMaterialRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @extends AbstractContentService<TeachingMaterial>
 */
class TeachingMaterialService extends AbstractContentService
{
    public function __construct(
        TeachingMaterialRepositoryInterface $materials,
        AuditLogService $audit,
        CacheRepository $cache,
        private readonly MediaService $media,
    ) {
        parent::__construct($materials, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::TEACHING_MATERIALS;
    }

    protected function cacheKeys(): array
    {
        return [CacheKeys::TEACHING_MATERIALS, CacheKeys::TEACHING_MATERIALS.'.members'];
    }

    /**
     * Published materials the visitor may see: members-only ones only for
     * approved members and staff.
     *
     * @return Collection<int, TeachingMaterial>
     */
    public function publishedFor(?User $viewer): Collection
    {
        /** @var TeachingMaterialRepositoryInterface $repository */
        $repository = $this->repository;

        if ($this->canSeeMembersMaterials($viewer)) {
            return $this->remember(CacheKeys::TEACHING_MATERIALS.'.members', fn (): Collection => $repository->publishedWithAccess(TeachingAccess::Public, TeachingAccess::Members));
        }

        return $this->remember(CacheKeys::TEACHING_MATERIALS, fn (): Collection => $repository->publishedWithAccess(TeachingAccess::Public));
    }

    public function canSeeMembersMaterials(?User $viewer): bool
    {
        return $viewer !== null && ($viewer->canAccessChat() || $viewer->canAccessAdmin());
    }

    public function canDownload(TeachingMaterial $material, ?User $viewer): bool
    {
        if (! $material->isPublished()) {
            return $viewer?->canAccessAdmin() ?? false;
        }

        return ! $material->isMembersOnly() || $this->canSeeMembersMaterials($viewer);
    }

    /**
     * Group a list by course (untitled courses under an empty key last).
     *
     * @param  Collection<int, TeachingMaterial>  $materials
     * @return array<string, Collection<int, TeachingMaterial>>
     */
    public function groupByCourse(Collection $materials): array
    {
        $grouped = $materials->groupBy(fn (TeachingMaterial $material): string => (string) $material->course)->sortKeys();

        if ($grouped->has('')) {
            $untitled = $grouped->pull('');
            $grouped->put('', $untitled);
        }

        return $grouped->map(fn ($items) => $items->values())->all();
    }

    /**
     * Attach the file on the disk matching the material's access level.
     */
    public function attachFile(TeachingMaterial $material, string|UploadedFile $file): Media
    {
        $media = $this->media->attach($material, $file, MediaCollections::FILE, [], $material->fileDisk());
        $this->flushCache();

        return $media;
    }

    public function removeFile(TeachingMaterial $material, ?User $actor = null): void
    {
        $file = $this->media->first($material, MediaCollections::FILE);

        if ($file !== null) {
            $this->media->remove($file, $actor);
        }

        $this->flushCache();
    }

    public function update($model, array $data): TeachingMaterial
    {
        $previousDisk = $model->fileDisk();

        /** @var TeachingMaterial $material */
        $material = parent::update($model, $data);

        // Keep the file on the disk that matches the (possibly new) access level.
        if ($material->fileDisk() !== $previousDisk) {
            $file = $this->media->first($material, MediaCollections::FILE);

            if ($file !== null) {
                $this->media->moveToDisk($file, $material, MediaCollections::FILE, $material->fileDisk());
            }
        }

        return $material;
    }

    protected function prepare(array $data, $existing): array
    {
        if (isset($data['type']) && is_string($data['type'])) {
            $data['type'] = TeachingMaterialType::from($data['type']);
        }

        if (isset($data['access']) && is_string($data['access'])) {
            $data['access'] = TeachingAccess::from($data['access']);
        }

        return $data;
    }
}
