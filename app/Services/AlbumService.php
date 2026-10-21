<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Album;
use App\Models\User;
use App\Repositories\Contracts\AlbumRepositoryInterface;
use App\Services\Concerns\GeneratesSlugs;
use App\Services\Concerns\GuardsImagePublishing;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @extends AbstractContentService<Album>
 */
class AlbumService extends AbstractContentService
{
    use GeneratesSlugs, GuardsImagePublishing;

    public function __construct(
        AlbumRepositoryInterface $albums,
        AuditLogService $audit,
        CacheRepository $cache,
        private readonly MediaService $media,
    ) {
        parent::__construct($albums, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::ALBUMS;
    }

    public function findPublishedBySlug(string $slug): ?Album
    {
        /** @var AlbumRepositoryInterface $repository */
        $repository = $this->repository;

        return $repository->findPublishedBySlug($slug);
    }

    /**
     * Append a photo to the album (archive treatment unless told otherwise).
     *
     * @param  array<string, mixed>  $properties  keyed by MediaProperties
     */
    public function addPhoto(Album $album, string|UploadedFile $file, array $properties = [], bool $preserveOriginal = false): Media
    {
        $properties[MediaProperties::TREATMENT] ??= MediaTreatment::Archive->value;

        $media = $this->media->attach($album, $file, MediaCollections::PHOTOS, $properties, null, $preserveOriginal);
        $this->flushCache();

        return $media;
    }

    public function removePhoto(Media $photo, ?User $actor = null): void
    {
        $this->media->remove($photo, $actor);
        $this->flushCache();
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorderPhotos(array $orderedIds): void
    {
        $this->media->reorder(array_map('intval', $orderedIds));
        $this->flushCache();
    }

    /**
     * Flag one photo as the album cover and clear the flag on the others.
     */
    public function setCover(Album $album, Media $photo): void
    {
        foreach ($this->media->inCollection($album, MediaCollections::PHOTOS) as $candidate) {
            $this->media->updateProperties($candidate, [
                MediaProperties::IS_COVER => $candidate->is($photo),
            ]);
        }

        $this->flushCache();
    }

    /**
     * File a photo under a decade (null: its year decides) and feature it on the wall.
     */
    public function updatePhotoArchive(Album $album, Media $photo, ?int $decade, bool $featured, ?User $actor = null): Media
    {
        if (! $this->belongsToAlbum($album, $photo)) {
            throw new InvalidArgumentException('The photo does not belong to this album.');
        }

        $photo = $this->media->updateArchiveFields($photo, $decade, $featured, $actor);
        $this->flushCache();

        return $photo;
    }

    protected function guardedImageCollections(): array
    {
        return [MediaCollections::PHOTOS];
    }

    protected function prepare(array $data, $existing): array
    {
        return $this->withUniqueSlug($data, $existing);
    }

    private function belongsToAlbum(Album $album, Media $photo): bool
    {
        return $photo->model_type === $album->getMorphClass()
            && (int) $photo->model_id === (int) $album->getKey()
            && $photo->collection_name === MediaCollections::PHOTOS;
    }
}
