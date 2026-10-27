<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Archive;
use App\Constants\AuditAction;
use App\Constants\ContentTypes;
use App\Constants\Locales;
use App\Constants\MediaProperties;
use App\Constants\MediaTreatment;
use App\Models\Media as LocalizedMedia;
use App\Models\User;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
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
    public function attach(HasMedia $model, string|UploadedFile $file, string $collection, array $properties = [], ?string $disk = null, bool $preserveOriginal = false, ?User $actor = null): Media
    {
        $properties = $this->normaliseProperties($properties) + $this->dimensions($file);

        $media = $this->media->attach($model, $file, $collection, $properties, $disk, $preserveOriginal);

        if ($actor !== null) {
            $this->audit->record($actor, AuditAction::MEDIA_UPLOADED, $model instanceof Model ? $model : null, $this->auditMeta($media));
        }

        return $media;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function updateProperties(Media $media, array $properties, ?User $actor = null): Media
    {
        $media = $this->media->updateProperties($media, $this->normaliseProperties($properties));

        if ($actor !== null) {
            $this->audit->record($actor, AuditAction::MEDIA_UPDATED, $media->model, $this->auditMeta($media));
        }

        return $media;
    }

    /**
     * Set an album photo's decade override (null: taken from its year) and featured flag.
     */
    public function updateArchiveFields(Media $media, ?int $decade, bool $featured, ?User $actor = null): Media
    {
        if ($decade !== null && ! in_array($decade, Archive::decades(), true)) {
            throw new InvalidArgumentException(sprintf('Unknown decade "%d".', $decade));
        }

        $media = $this->media->updateArchiveFields($media, $decade, $featured);

        if ($actor !== null) {
            $this->audit->record($actor, AuditAction::MEDIA_UPDATED, $media->model, $this->auditMeta($media) + [Archive::DECADE => $decade, Archive::FEATURED => $featured]);
        }

        return $media;
    }

    public function remove(Media $media, ?User $actor = null): bool
    {
        $this->audit->record($actor, AuditAction::MEDIA_DELETED, null, $this->auditMeta($media));

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

    public function findOrFail(int $id): Media
    {
        return $this->media->findOrFail($id);
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
     * Move a media row onto another model's collection, leaving its files in place.
     *
     * @param  array<string, mixed>  $properties  keyed by MediaProperties
     */
    public function reassign(Media $media, HasMedia $model, string $collection, array $properties = []): Media
    {
        return $this->media->reassign($media, $model, $collection, $this->normaliseProperties($properties));
    }

    /**
     * The editable details of an image for the admin forms: each property in
     * English (never the editor's language) and, under 'translations', the
     * translated alt text, caption and place per language, without fallback.
     *
     * @return array<string, mixed>
     */
    public function details(Media $media): array
    {
        $details = [];

        foreach ([MediaProperties::ALT_TEXT, MediaProperties::CAPTION, MediaProperties::PLACE, MediaProperties::YEAR, MediaProperties::TREATMENT, MediaProperties::CREDIT, MediaProperties::PERMISSIONS_NOTE] as $key) {
            $details[$key] = $media instanceof LocalizedMedia ? $media->getTranslatedProperty($key, Locales::DEFAULT) : $media->getCustomProperty($key);
        }

        foreach (Locales::prefixed() as $locale) {
            foreach ([MediaProperties::ALT_TEXT, MediaProperties::CAPTION, MediaProperties::PLACE] as $key) {
                $details[MediaProperties::TRANSLATIONS][$locale][$key] = $media instanceof LocalizedMedia ? $media->getTranslatedProperty($key, $locale) : null;
            }
        }

        return $details;
    }

    /**
     * Whether an image carries everything the public site needs (alt, caption, place, year).
     *
     * @return list<string> missing property keys
     */
    public function missingForPublishing(Media $media): array
    {
        // English is what publishing needs, whatever language the editor works in.
        return array_values(array_filter(
            MediaProperties::requiredForPublishing(),
            fn (string $key): bool => blank($media instanceof LocalizedMedia ? $media->getTranslatedProperty($key, Locales::DEFAULT) : $media->getCustomProperty($key)),
        ));
    }

    public function isReadyForPublishing(Media $media): bool
    {
        return $this->missingForPublishing($media) === [];
    }

    /**
     * Every image on the site for the media library.
     *
     * @param  array<string, mixed>  $filters  keyed by MediaRepositoryInterface::FILTER_*
     * @return LengthAwarePaginator<int, Media>
     */
    public function library(?string $term = null, array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        return $this->media->searchImages($term, $filters, $perPage);
    }

    public function countIncompleteImages(): int
    {
        return $this->media->countIncompleteImages();
    }

    public function countMissingAltText(): int
    {
        return $this->media->countMissingAltText();
    }

    /**
     * Where an image is used: the content type and record it belongs to, with
     * a link to that record's admin screen, so editors can see what a deletion
     * would affect.
     *
     * @return array{type: ?string, type_label: string, label: string, collection: string, url: ?string, trashed: bool}
     */
    public function usage(Media $media): array
    {
        $type = ContentTypes::keyForModel((string) $media->model_type);
        /** @var Model|null $owner */
        $owner = $media->model_type && $media->model_id
            ? ($media->model_type)::withoutGlobalScopes()->find($media->model_id)
            : null;

        $typeLabel = $type === null ? class_basename((string) $media->model_type) : __(ContentTypes::get($type)['singular']);
        $label = $owner?->getAttribute('title') ?? $owner?->getAttribute('name') ?? ($owner === null ? __('Not attached to any content') : $typeLabel.' #'.$owner->getKey());
        $trashed = $owner !== null && method_exists($owner, 'trashed') && $owner->trashed();

        $url = null;

        if ($type !== null && $owner !== null && ! $trashed) {
            $url = $type === ContentTypes::PROFILE
                ? route(ContentTypes::editRoute($type))
                : route(ContentTypes::editRoute($type), [ContentTypes::routeParameter($type) => $owner->getKey()]);
        }

        return [
            'type' => $type,
            'type_label' => $typeLabel,
            'label' => (string) $label,
            'collection' => (string) $media->collection_name,
            'url' => $url,
            'trashed' => $trashed,
        ];
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

        // Nested translations (['translations' => ['ar' => ['alt_text' => ...]]]) become their paths.
        if (is_array($properties[MediaProperties::TRANSLATIONS] ?? null)) {
            foreach ($properties[MediaProperties::TRANSLATIONS] as $locale => $translated) {
                foreach (is_array($translated) ? $translated : [] as $property => $value) {
                    $properties[MediaProperties::translationPath((string) $locale, (string) $property)] = $value;
                }
            }

            unset($properties[MediaProperties::TRANSLATIONS]);
        }

        foreach ($properties as $key => $value) {
            if (MediaProperties::isTranslationPath((string) $key)) {
                // Another language's alt text, caption, place or note: a trimmed string, or null to forget it.
                $value = is_string($value) ? trim($value) : $value;
                $clean[$key] = $value === '' ? null : $value;

                continue;
            }

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

    /**
     * @return array<string, mixed>
     */
    private function auditMeta(Media $media): array
    {
        return [
            'media_id' => $media->getKey(),
            'file_name' => $media->file_name,
            'collection' => $media->collection_name,
            'model_type' => $media->model_type,
            'model_id' => $media->model_id,
        ];
    }
}
