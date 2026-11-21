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

    /** Filter key for searchImages(): true keeps only images without alt text. */
    public const string FILTER_MISSING_ALT = 'missing_alt';

    /** Filter key for searchImages(): true keeps only images whose record is gone or in the trash. */
    public const string FILTER_UNUSED = 'unused';

    /** Filter key for searchImages(): album id. */
    public const string FILTER_ALBUM = 'album';

    /** Columns searchImages() sorts by. */
    public const array SORT_COLUMNS = ['created_at', 'file_name'];

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

    /**
     * Set the archive columns of an album photo (Archive::DECADE, Archive::FEATURED).
     */
    public function updateArchiveFields(Media $media, ?int $decade, bool $featured): Media;

    /**
     * Swap the file of a media row for another, keeping the row itself: its id,
     * owner, collection, order, archive columns and custom properties. The old
     * file and its conversions are deleted and the conversions are made again.
     * Properties are merged in (null forgets one).
     *
     * @param  array<string, mixed>  $properties
     */
    public function replaceFile(Media $media, string|UploadedFile $file, array $properties = []): Media;

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
     * Hand a media row to another model and collection without touching its
     * files (same disk, same path): one row update, so it can run inside the
     * caller's transaction. Custom properties are merged in.
     *
     * @param  array<string, mixed>  $properties
     */
    public function reassign(Media $media, HasMedia $model, string $collection, array $properties = []): Media;

    /**
     * Number of media rows attached to the given model.
     */
    public function countFor(HasMedia $model): int;

    /**
     * Copy every file of one model (each collection, with its details) onto another.
     */
    public function copyAll(HasMedia $from, HasMedia $to): int;

    /**
     * Every image across the site (the image collections), newest first, for
     * the media library. The term matches file name, alt text and caption;
     * filters use the FILTER_* keys (a collection list matches any of them).
     * `sortBy` is one of SORT_COLUMNS (null: newest first).
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Media>
     */
    public function searchImages(?string $term = null, array $filters = [], int $perPage = 24, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator;

    /**
     * How many images searchImages() would find.
     *
     * @param  array<string, mixed>  $filters
     */
    public function countImages(?string $term = null, array $filters = []): int;

    /**
     * Number of images still missing one of the properties required for publishing.
     */
    public function countIncompleteImages(): int;

    /**
     * Number of images with no alt text.
     */
    public function countMissingAltText(): int;
}
