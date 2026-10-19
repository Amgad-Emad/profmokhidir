<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Constants\VideoProvider;
use App\Models\User;
use App\Models\Video;
use App\Repositories\Contracts\VideoRepositoryInterface;
use App\Services\Concerns\GeneratesSlugs;
use App\Services\Concerns\GuardsImagePublishing;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @extends AbstractContentService<Video>
 */
class VideoService extends AbstractContentService
{
    use GeneratesSlugs, GuardsImagePublishing;

    public function __construct(
        VideoRepositoryInterface $videos,
        AuditLogService $audit,
        CacheRepository $cache,
        private readonly MediaService $media,
    ) {
        parent::__construct($videos, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::VIDEOS;
    }

    public function findPublishedBySlug(string $slug): ?Video
    {
        /** @var VideoRepositoryInterface $repository */
        $repository = $this->repository;

        return $repository->findPublishedBySlug($slug);
    }

    /**
     * @param  array<string, mixed>  $properties  keyed by MediaProperties
     */
    public function setThumbnail(Video $video, string|UploadedFile $file, array $properties = []): Media
    {
        $properties[MediaProperties::TREATMENT] ??= MediaTreatment::Archive->value;

        $media = $this->media->attach($video, $file, MediaCollections::THUMBNAIL, $properties);
        $this->flushCache();

        return $media;
    }

    public function setVideoFile(Video $video, string|UploadedFile $file): Media
    {
        $media = $this->media->attach($video, $file, MediaCollections::VIDEO);
        $this->flushCache();

        return $media;
    }

    public function removeMedia(Video $video, string $collection, ?User $actor = null): void
    {
        $media = $this->media->first($video, $collection);

        if ($media !== null) {
            $this->media->remove($media, $actor);
        }

        $this->flushCache();
    }

    protected function guardedImageCollections(): array
    {
        return [MediaCollections::THUMBNAIL];
    }

    protected function prepare(array $data, $existing): array
    {
        if (isset($data['provider']) && is_string($data['provider'])) {
            $data['provider'] = VideoProvider::from($data['provider']);
        }

        return $this->withUniqueSlug($data, $existing);
    }
}
