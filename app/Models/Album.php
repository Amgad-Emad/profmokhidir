<?php

namespace App\Models;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Models\Concerns\HasResponsiveImages;
use App\Models\Concerns\HasTranslatableContent;
use App\Models\Concerns\Publishable;
use Database\Factories\AlbumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Photos are the ordered `photos` media collection (media.order_column).
 */
#[Fillable(['title', 'slug', 'description', 'status', 'published_at', 'sort_order'])]
class Album extends Model implements HasMedia
{
    /** @use HasFactory<AlbumFactory> */
    use HasFactory, HasResponsiveImages, HasTranslatableContent, InteractsWithMedia, Publishable, SoftDeletes;

    /**
     * Language-specific columns (English and Arabic); the first is the primary one.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'description'];

    /**
     * Columns folded into the search column (App\Support\SearchText).
     *
     * @var list<string>
     */
    protected array $searchColumns = ['title', 'description'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollections::PHOTOS)
            ->useDisk(Disks::PUBLIC)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerResponsiveWebpConversions($media, [MediaCollections::PHOTOS], responsive: true);
    }

    /**
     * @return MediaCollection<int, Media>
     */
    public function photos(): MediaCollection
    {
        return $this->getMedia(MediaCollections::PHOTOS);
    }

    /**
     * The photo flagged as cover, otherwise the first photo in order.
     */
    public function cover(): ?Media
    {
        $photos = $this->photos();

        return $photos->first(fn (Media $media): bool => (bool) $media->getCustomProperty(MediaProperties::IS_COVER, false))
            ?? $photos->first();
    }
}
