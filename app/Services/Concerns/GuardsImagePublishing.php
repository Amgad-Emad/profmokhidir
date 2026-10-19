<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Exceptions\ImagesNotReadyException;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * For content with image collections: refuse to publish while an image still
 * lacks the properties the public site needs. The using service must have a
 * `$media` MediaService property and name its image collections.
 */
trait GuardsImagePublishing
{
    /**
     * Image collections checked before publishing.
     *
     * @return list<string>
     */
    abstract protected function guardedImageCollections(): array;

    protected function assertPublishable(Model $model): void
    {
        if (! $model instanceof HasMedia) {
            return;
        }

        $missing = $this->incompleteImages($model);

        if ($missing !== []) {
            throw new ImagesNotReadyException($missing);
        }
    }

    /**
     * Media id => missing property keys, for every image that is not ready.
     *
     * @return array<int, list<string>>
     */
    public function incompleteImages(HasMedia $model): array
    {
        $missing = [];

        foreach ($this->guardedImageCollections() as $collection) {
            foreach ($this->media->inCollection($model, $collection) as $image) {
                $keys = $this->media->missingForPublishing($image);

                if ($keys !== []) {
                    $missing[(int) $image->getKey()] = $keys;
                }
            }
        }

        return $missing;
    }
}
