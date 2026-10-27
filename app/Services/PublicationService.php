<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Constants\Catalogue;
use App\Constants\MediaCollections;
use App\Constants\PublicationType;
use App\Models\Publication;
use App\Models\User;
use App\Repositories\Contracts\PublicationRepositoryInterface;
use App\Services\Concerns\GeneratesSlugs;
use App\Support\PublicationFilters;
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
        return [CacheKeys::PUBLICATIONS, CacheKeys::PUBLICATIONS_FILTERS, CacheKeys::PUBLICATIONS_LATEST, CacheKeys::PUBLICATIONS_FEATURED];
    }

    public function findPublishedBySlug(string $slug): ?Publication
    {
        return $this->publications()->findPublishedBySlug($slug);
    }

    /**
     * The public catalogue with its type, year-range and topic filters.
     *
     * @return LengthAwarePaginator<int, Publication>
     */
    public function publishedFiltered(PublicationFilters $filters = new PublicationFilters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->publications()->paginatePublishedFiltered($filters, $perPage);
    }

    /**
     * Matching records per decade (newest first, undated under ""), for the group counts.
     *
     * @return array<int|string, int>
     */
    public function decadeCounts(PublicationFilters $filters = new PublicationFilters): array
    {
        return $this->publications()->publishedDecadeCounts($filters);
    }

    /**
     * Available filter values among published publications (cached).
     *
     * @return array{years: list<int>, types: list<PublicationType>, topics: list<string>}
     */
    public function filters(): array
    {
        return $this->remember(CacheKeys::PUBLICATIONS_FILTERS, fn (): array => [
            'years' => $this->publications()->publishedYears(),
            'types' => $this->publications()->publishedTypes(),
            'topics' => $this->publications()->publishedTopics(),
        ]);
    }

    /**
     * The "Selected works" strip: published records marked as featured (cached).
     *
     * @return Collection<int, Publication>
     */
    public function featured(int $limit = Catalogue::SELECTED_WORKS_LIMIT): Collection
    {
        return $this->remember(CacheKeys::PUBLICATIONS_FEATURED, fn (): Collection => $this->publications()->publishedFeatured($limit));
    }

    /**
     * @return Collection<int, Publication>
     */
    public function latest(int $limit = 3): Collection
    {
        return $this->remember(CacheKeys::PUBLICATIONS_LATEST, fn (): Collection => $this->publications()->latestPublished($limit));
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

        if (array_key_exists('is_featured', $data)) {
            $data['is_featured'] = (bool) $data['is_featured'];
        }

        if (array_key_exists('topic', $data)) {
            $topic = is_string($data['topic']) ? trim($data['topic']) : '';
            $data['topic'] = $topic === '' ? null : $topic;
        }

        if (isset($data['doi']) && is_string($data['doi'])) {
            $data['doi'] = preg_replace('#^https?://(dx\.)?doi\.org/#i', '', trim($data['doi']));
        }

        return $this->withEnglishSlug($data, $existing);
    }

    private function publications(): PublicationRepositoryInterface
    {
        /** @var PublicationRepositoryInterface $repository */
        $repository = $this->repository;

        return $repository;
    }
}
