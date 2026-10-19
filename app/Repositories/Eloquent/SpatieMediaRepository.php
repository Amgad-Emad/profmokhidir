<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class SpatieMediaRepository implements MediaRepositoryInterface
{
    public function find(int $id): ?Media
    {
        return Media::query()->find($id);
    }

    public function findOrFail(int $id): Media
    {
        return Media::query()->findOrFail($id);
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

    public function countFor(HasMedia $model): int
    {
        return $model->media()->count();
    }
}
