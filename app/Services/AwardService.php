<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Award;
use App\Models\User;
use App\Repositories\Contracts\AwardRepositoryInterface;
use App\Services\Concerns\GuardsImagePublishing;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;

/**
 * @extends AbstractContentService<Award>
 */
class AwardService extends AbstractContentService
{
    use GuardsImagePublishing;

    public function __construct(
        AwardRepositoryInterface $awards,
        AuditLogService $audit,
        CacheRepository $cache,
        private readonly MediaService $media,
    ) {
        parent::__construct($awards, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::AWARDS;
    }

    /**
     * Photographs of the certificate, medal or shield, in display order.
     *
     * @return MediaCollection<int, Media>
     */
    public function images(Award $award): MediaCollection
    {
        return $this->media->inCollection($award, MediaCollections::AWARD_IMAGES);
    }

    /**
     * @param  array<string, mixed>  $properties  keyed by MediaProperties
     */
    public function addImage(Award $award, string|UploadedFile $file, array $properties = [], bool $preserveOriginal = false): Media
    {
        $properties[MediaProperties::TREATMENT] ??= MediaTreatment::Archive->value;

        $media = $this->media->attach($award, $file, MediaCollections::AWARD_IMAGES, $properties, preserveOriginal: $preserveOriginal);
        $this->flushCache();

        return $media;
    }

    public function removeImage(Media $media, ?User $actor = null): void
    {
        $this->media->remove($media, $actor);
        $this->flushCache();
    }

    protected function guardedImageCollections(): array
    {
        return [MediaCollections::AWARD_IMAGES];
    }
}
