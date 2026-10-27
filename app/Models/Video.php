<?php

namespace App\Models;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\VideoProvider;
use App\Models\Concerns\HasResponsiveImages;
use App\Models\Concerns\HasTranslatableContent;
use App\Models\Concerns\Publishable;
use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Collections: `video` (single, optional uploaded file) and `thumbnail` (single poster image).
 * `decade` overrides the decade its date gives; `is_featured` puts it at the top of Videos.
 */
#[Fillable([
    'title', 'slug', 'date', 'decade', 'is_featured', 'event', 'location', 'description', 'provider', 'media_url', 'captions_url',
    'status', 'published_at', 'sort_order',
])]
class Video extends Model implements HasMedia
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory, HasResponsiveImages, HasTranslatableContent, InteractsWithMedia, Publishable, SoftDeletes;

    /**
     * Language-specific columns (English and Arabic); the first is the primary one.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'event', 'location', 'description'];

    /**
     * Columns folded into the search column (App\Support\SearchText).
     *
     * @var list<string>
     */
    protected array $searchColumns = ['title', 'event', 'location', 'description'];

    protected string $orderDateColumn = 'date';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'decade' => 'integer',
            'is_featured' => 'boolean',
            'provider' => VideoProvider::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollections::VIDEO)->useDisk(Disks::PUBLIC)->singleFile();
        $this->addMediaCollection(MediaCollections::THUMBNAIL)->useDisk(Disks::PUBLIC)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerResponsiveWebpConversions($media, [MediaCollections::THUMBNAIL], responsive: true);
    }

    public function videoFile(): ?Media
    {
        return $this->getFirstMedia(MediaCollections::VIDEO);
    }

    public function thumbnail(): ?Media
    {
        return $this->getFirstMedia(MediaCollections::THUMBNAIL);
    }
}
