<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\Archive;
use App\Constants\ContentTypes;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Models\Album;
use App\Models\Media as LocalizedMedia;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class SpatieMediaRepository implements MediaRepositoryInterface
{
    public function find(int $id): ?Media
    {
        return LocalizedMedia::query()->find($id);
    }

    public function findOrFail(int $id): Media
    {
        return LocalizedMedia::query()->findOrFail($id);
    }

    public function attach(HasMedia $model, string|UploadedFile $file, string $collection, array $properties = [], ?string $disk = null, bool $preserveOriginal = false): Media
    {
        return $model->addMedia($file)
            ->preservingOriginal($preserveOriginal)
            ->withCustomProperties($properties)
            ->toMediaCollection($collection, $disk ?? '');
    }

    public function inCollection(HasMedia $model, string $collection): MediaCollection
    {
        return $model->getMedia($collection);
    }

    public function first(HasMedia $model, string $collection): ?Media
    {
        return $model->getFirstMedia($collection);
    }

    public function updateProperties(Media $media, array $properties): Media
    {
        foreach ($properties as $key => $value) {
            if ($value === null) {
                $media->forgetCustomProperty($key);

                continue;
            }

            $media->setCustomProperty($key, $value);
        }

        $media->save();

        return $media;
    }

    public function updateArchiveFields(Media $media, ?int $decade, bool $featured): Media
    {
        $media->forceFill([Archive::DECADE => $decade, Archive::FEATURED => $featured])->save();

        return $media;
    }

    public function delete(Media $media): bool
    {
        return (bool) $media->delete();
    }

    public function clearCollection(HasMedia $model, string $collection): void
    {
        $model->clearMediaCollection($collection);
    }

    public function reorder(array $orderedIds): void
    {
        Media::setNewOrder($orderedIds);
    }

    public function moveToDisk(Media $media, HasMedia $model, string $collection, string $disk): Media
    {
        return $media->move($model, $collection, $disk);
    }

    public function reassign(Media $media, HasMedia $model, string $collection, array $properties = []): Media
    {
        /** @var Model&HasMedia $model */
        $media->forceFill([
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'collection_name' => $collection,
        ]);

        foreach ($properties as $key => $value) {
            $media->setCustomProperty($key, $value);
        }

        $media->save();

        return $media;
    }

    public function countFor(HasMedia $model): int
    {
        return $model->media()->count();
    }

    public function copyAll(HasMedia $from, HasMedia $to): int
    {
        /** @var Model&HasMedia $from */
        $copied = 0;

        foreach ($from->media()->orderBy('order_column')->get() as $media) {
            $media->copy($to, $media->collection_name, $media->disk);
            $copied++;
        }

        return $copied;
    }

    public function searchImages(?string $term = null, array $filters = [], int $perPage = 24, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $direction = $sortDirection === 'asc' ? 'asc' : 'desc';
        $column = in_array($sortBy, self::SORT_COLUMNS, true) ? $sortBy : 'created_at';

        return $this->filteredImages($term, $filters)
            ->orderBy($column, $column === 'created_at' && $sortBy === null ? 'desc' : $direction)
            ->orderBy('id', $column === 'created_at' && $sortBy === null ? 'desc' : $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countImages(?string $term = null, array $filters = []): int
    {
        return $this->filteredImages($term, $filters)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Media>
     */
    private function filteredImages(?string $term, array $filters): Builder
    {
        $term = trim((string) $term);
        $collections = array_values(array_filter((array) ($filters[self::FILTER_COLLECTION] ?? []), 'filled'));

        return $this->imagesQuery()
            ->when($term !== '', function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('file_name', 'like', $like)->orWhere('name', 'like', $like);

                    // JSON_UNQUOTE yields a binary collation, so compare lower-cased.
                    foreach ([MediaProperties::ALT_TEXT, MediaProperties::CAPTION, MediaProperties::PLACE] as $property) {
                        $query->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(custom_properties, ?))) LIKE ?', ['$.'.$property, mb_strtolower($like)]);
                    }
                });
            })
            ->when($collections !== [], fn (Builder $query) => $query->whereIn('collection_name', $collections))
            ->when(filled($filters[self::FILTER_TREATMENT] ?? null), fn (Builder $query) => $query->where('custom_properties->'.MediaProperties::TREATMENT, $filters[self::FILTER_TREATMENT]))
            ->when(filled($filters[self::FILTER_MODEL] ?? null), fn (Builder $query) => $query->where('model_type', $filters[self::FILTER_MODEL]))
            ->when((bool) ($filters[self::FILTER_INCOMPLETE] ?? false), fn (Builder $query) => $this->whereIncomplete($query))
            ->when((bool) ($filters[self::FILTER_MISSING_ALT] ?? false), fn (Builder $query) => $this->whereMissing($query, MediaProperties::ALT_TEXT))
            ->when((bool) ($filters[self::FILTER_UNUSED] ?? false), fn (Builder $query) => $this->whereUnused($query))
            ->when(filled($filters[self::FILTER_ALBUM] ?? null), fn (Builder $query) => $query->where('model_type', (new Album)->getMorphClass())->where('model_id', (int) $filters[self::FILTER_ALBUM]));
    }

    public function countIncompleteImages(): int
    {
        return $this->whereIncomplete($this->imagesQuery())->count();
    }

    public function countMissingAltText(): int
    {
        return $this->whereMissing($this->imagesQuery(), MediaProperties::ALT_TEXT)->count();
    }

    /**
     * @return Builder<Media>
     */
    private function imagesQuery(): Builder
    {
        return LocalizedMedia::query()->whereIn('collection_name', MediaCollections::images());
    }

    /**
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    private function whereMissing(Builder $query, string $property): Builder
    {
        $path = 'custom_properties->'.$property;

        return $query->where(fn (Builder $query) => $query->whereNull($path)->orWhere($path, ''));
    }

    /**
     * Images whose record no longer exists or sits in the trash: nothing on
     * the site shows them.
     *
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    private function whereUnused(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            foreach (ContentTypes::all() as $type) {
                /** @var Model $owner */
                $owner = new $type['model'];

                $query->orWhere(fn (Builder $query) => $query
                    ->where('model_type', $owner->getMorphClass())
                    ->whereNotIn('model_id', $owner->newQuery()->select($owner->getKeyName())));
            }
        });
    }

    /**
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    private function whereIncomplete(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            foreach (MediaProperties::requiredForPublishing() as $property) {
                $path = 'custom_properties->'.$property;

                $query->orWhereNull($path)->orWhere($path, '');
            }
        });
    }
}
