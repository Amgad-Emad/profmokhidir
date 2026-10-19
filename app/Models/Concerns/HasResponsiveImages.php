<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Constants\MediaConversions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Registers the brand's responsive WebP conversions (480, 960, 1600) as queued
 * conversions on the given image collections. Models call this from
 * registerMediaConversions().
 */
trait HasResponsiveImages
{
    /**
     * @param  list<string>  $collections
     */
    protected function registerResponsiveWebpConversions(?Media $media, array $collections, bool $responsive = false): void
    {
        foreach (MediaConversions::widths() as $name => $width) {
            $conversion = $this->addMediaConversion($name)
                ->format(MediaConversions::FORMAT)
                ->width($width)
                ->queued()
                ->performOnCollections(...$collections);

            if ($responsive) {
                $conversion->withResponsiveImages();
            }
        }
    }
}
