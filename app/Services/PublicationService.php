<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Constants\MediaCollections;
use App\Constants\PublicationType;
use App\Models\Publication;
use App\Models\User;
use App\Repositories\Contracts\PublicationRepositoryInterface;
use App\Services\Concerns\GeneratesSlugs;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @extends AbstractContentService<Publication>
 */
class PublicationService extends AbstractContentService
{
    use GeneratesSlugs;

    public function __construct(
        PublicationRepositoryInterface $publications,
        AuditLogService $audit,
        CacheRepository $cache,
        private readonly MediaService $media,
    ) {
        parent::__construct($publications, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::PUBLICATIONS;
    }

    protected function cacheKeys(): array
    {
        return [CacheKeys::PUBLICATIONS, CacheKeys::PUBLICATIONS.'.filters', CacheKeys::PUBLICATIONS.'.latest'];
    }

    public function findPublishedBySlug(string $slug): ?Publication
    {
        return $this->publications()->findPublishedBySlug($slug);
    }

    /**
     * Public listing with the type and year filters.
     *
     * @return LengthAwarePaginator<int, Publication>
     */
    public function publishedFiltered(?string $type = null, ?int $year = null, int $perPage = 20): LengthAwarePaginator
    {
        $typeEnum = $type !== null && $type !== '' ? PublicationType::tryFrom($type) : null;

        return $this->publications()->paginatePublishedFiltered($typeEnum, $year, $perPage);
    }

    /**
     * Available filter values among published publications (cached).
     *
     * @return array{years: list<int>, types: list<PublicationType>}
     */
    public function filters(): array
    {
        return $this->remember(CacheKeys::PUBLICATIONS.'.filters', fn (): array => [
            'years' => $this->publications()->publishedYears(),
            'types' => $this->publications()->publishedTypes(),
        ]);
    }

    /**
     * @return Collection<int, Publication>
     */
    public function latest(int $limit = 3): Collection
    {
        return $this->remember(CacheKeys::PUBLICATIONS.'.latest', fn (): Collection => $this->publications()->latestPublished($limit));
    }

    public function attachFile(Publication $publication, string|UploadedFile $file): Media
    {
        $media = $this->media->attach($publication, $file, MediaCollections::FILE);
        $this->flushCache();

        return $media;
    }

    public function removeFile(Publication $publication, ?User $actor = null): void
    {
        $file = $this->media->first($publication, MediaCollections::FILE);

        if ($file !== null) {
            $this->media->remove($file, $actor);
        }

        $this->flushCache();
    }

    protected function prepare(array $data, $existing): array
    {
        if (isset($data['type']) && is_string($data['type'])) {
            $data['type'] = PublicationType::from($data['type']);
        }

        if (isset($data['doi']) && is_string($data['doi'])) {
            $data['doi'] = preg_replace('#^https?://(dx\.)?doi\.org/#i', '', trim($data['doi']));
        }

        return $this->withUniqueSlug($data, $existing);
    }

    private function publications(): PublicationRepositoryInterface
    {
        /** @var PublicationRepositoryInterface $repository */
        $repository = $this->repository;

        return $repository;
    }
}
