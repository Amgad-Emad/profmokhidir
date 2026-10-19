<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\User;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * All uploads go through here so every file carries the brand's custom
 * properties and lands on the right disk.
 */
class MediaService
{
    public function __construct(
        private readonly MediaRepositoryInterface $media,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $properties  keyed by MediaProperties
     */
    public function attach(HasMedia $model, string|UploadedFile $file, string $collection, array $properties = [], ?string $disk = null, bool $preserveOriginal = false): Media
    {
        $properties = $this->normaliseProperties($properties) + $this->dimensions($file);

        return $this->media->attach($model, $file, $collection, $properties, $disk, $preserveOriginal);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function updateProperties(Media $media, array $properties): Media
    {
        return $this->media->updateProperties($media, $this->normaliseProperties($properties));
    }

    public function remove(Media $media, ?User $actor = null): bool
    {
        $this->audit->record($actor, AuditAction::MEDIA_DELETED, null, [
            'media_id' => $media->getKey(),
            'file_name' => $media->file_name,
            'collection' => $media->collection_name,
            'model_type' => $media->model_type,
            'model_id' => $media->model_id,
        ]);

        return $this->media->delete($media);
    }

    public function clear(HasMedia $model, string $collection): void
    {
        $this->media->clearCollection($model, $collection);
    }

    /**
     * @return MediaCollection<int, Media>
     */
    public function inCollection(HasMedia $model, string $collection): MediaCollection
    {
        return $this->media->inCollection($model, $collection);
    }

    public function first(HasMedia $model, string $collection): ?Media
    {
        return $this->media->first($model, $collection);
    }

    public function find(int $id): ?Media
    {
        return $this->media->find($id);
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->media->reorder($orderedIds);
    }

    public function moveToDisk(Media $media, HasMedia $model, string $collection, string $disk): Media
    {
        return $this->media->moveToDisk($media, $model, $collection, $disk);
    }

    /**
     * Whether an image carries everything the public site needs (alt, caption, place, year).
     *
     * @return list<string> missing property keys
     */
    public function missingForPublishing(Media $media): array
    {
        return array_values(array_filter(
            MediaProperties::requiredForPublishing(),
            fn (string $key): bool => blank($media->getCustomProperty($key)),
        ));
    }

    /**
     * Pixel size of an image upload, so the public site can reserve its space
     * before it loads. Empty for anything that is not an image.
     *
     * @return array<string, int>
     */
    private function dimensions(string|UploadedFile $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return [];
        }

        $size = @getimagesize($path);

        if (! is_array($size) || (int) $size[0] <= 0 || (int) $size[1] <= 0) {
            return [];
        }

        return [MediaProperties::WIDTH => (int) $size[0], MediaProperties::HEIGHT => (int) $size[1]];
    }

    /**
     * Keep only known property keys and validate the treatment.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function normaliseProperties(array $properties): array
    {
        $clean = [];

        foreach ($properties as $key => $value) {
            if (! in_array($key, MediaProperties::all(), true)) {
                throw new InvalidArgumentException(sprintf('Unknown media property "%s".', $key));
            }

            if ($key === MediaProperties::TREATMENT && $value !== null) {
                $value = $value instanceof MediaTreatment ? $value->value : (string) $value;

                if (! MediaTreatment::isValid($value)) {
                    throw new InvalidArgumentException(sprintf('Unknown media treatment "%s".', $value));
                }
            }

            if (in_array($key, [MediaProperties::YEAR, MediaProperties::WIDTH, MediaProperties::HEIGHT], true) && $value !== null) {
                $value = (int) $value;
            }

            if ($key === MediaProperties::IS_COVER && $value !== null) {
                $value = (bool) $value;
            }

            $clean[$key] = is_string($value) ? trim($value) : $value;
        }

        return $clean;
    }
}
