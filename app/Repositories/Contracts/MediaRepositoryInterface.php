<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The only place that calls spatie/laravel-medialibrary directly.
 */
interface MediaRepositoryInterface
{
    /** Filter key for searchImages(): collection name. */
    public const string FILTER_COLLECTION = 'collection';

    /** Filter key for searchImages(): MediaTreatment value. */
    public const string FILTER_TREATMENT = 'treatment';

    /** Filter key for searchImages(): owning model class. */
    public const string FILTER_MODEL = 'model';

    /** Filter key for searchImages(): true keeps only images missing a required property. */
    public const string FILTER_INCOMPLETE = 'incomplete';

    public function find(int $id): ?Media;

    public function findOrFail(int $id): Media;

    /**
     * Attach a file to a model's collection. Custom properties are keyed by MediaProperties.
     *
     * @param  array<string, mixed>  $properties
     */
    public function attach(HasMedia $model, string|UploadedFile $file, string $collection, array $properties = [], ?string $disk = null, bool $preserveOriginal = false): Media;

    /**
     * @return MediaCollection<int, Media>
     */
    public function inCollection(HasMedia $model, string $collection): MediaCollection;

    public function first(HasMedia $model, string $collection): ?Media;

    /**
     * @param  array<string, mixed>  $properties
     */
    public function updateProperties(Media $media, array $properties): Media;

    public function delete(Media $media): bool;

    public function clearCollection(HasMedia $model, string $collection): void;

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void;

    /**
     * Move a file to another disk (and/or collection) on the same or another model.
     */
    public function moveToDisk(Media $media, HasMedia $model, string $collection, string $disk): Media;

    /**
     * Number of media rows attached to the given model.
     */
    public function countFor(HasMedia $model): int;

    /**
     * Every image across the site (the image collections), newest first, for
     * the media library. The term matches file name, alt text and caption;
     * filters use the FILTER_* keys.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Media>
     */
    public function searchImages(?string $term = null, array $filters = [], int $perPage = 24): LengthAwarePaginator;

    /**
     * Number of images still missing one of the properties required for publishing.
     */
    public function countIncompleteImages(): int;
}
