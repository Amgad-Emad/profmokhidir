<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Archive;
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
use Illuminate\Database\Eloquent\Collection;
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
     * The recording that opens the lecture hall: the first featured one in
     * display order, otherwise simply the first.
     */
    public function featured(): ?Video
    {
        $videos = $this->published();

        return $videos->first(fn (Video $video): bool => $video->is_featured) ?? $videos->first();
    }

    /**
     * Other published recordings of the same event, in display order.
     *
     * @return Collection<int, Video>
     */
    public function fromSameEvent(Video $video, int $limit = Archive::RELATED_LIMIT): Collection
    {
        if (blank($video->event)) {
            return new Collection;
        }

        $event = mb_strtolower(trim((string) $video->event));

        return $this->published()
            ->filter(fn (Video $other): bool => ! $other->is($video) && mb_strtolower(trim((string) $other->event)) === $event)
            ->take($limit)
            ->values();
    }

    /**
     * The decade a recording is filed under: the editor's override, otherwise its date's.
     */
    public function decadeOf(Video $video): ?int
    {
        if ($video->decade !== null) {
            return (int) $video->decade;
        }

        return $video->date === null ? null : Archive::decadeOf((int) $video->date->format('Y'));
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

        if (array_key_exists(Archive::DECADE, $data)) {
            $data[Archive::DECADE] = blank($data[Archive::DECADE]) ? null : (int) $data[Archive::DECADE];
        }

        if (array_key_exists(Archive::FEATURED, $data)) {
            $data[Archive::FEATURED] = (bool) $data[Archive::FEATURED];
        }

        return $this->withEnglishSlug($data, $existing);
    }
}
