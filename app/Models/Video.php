<?php

namespace App\Models;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\VideoProvider;
use App\Models\Concerns\HasResponsiveImages;
use App\Models\Concerns\Publishable;
use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Collections: `video` (single, optional uploaded file) and `thumbnail` (single poster image).
 */
#[Fillable([
    'title', 'slug', 'date', 'event', 'location', 'description', 'provider', 'media_url', 'captions_url',
    'status', 'published_at', 'sort_order',
])]
class Video extends Model implements HasMedia
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory, HasResponsiveImages, InteractsWithMedia, Publishable;

    protected string $orderDateColumn = 'date';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
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
