<?php

namespace App\Models;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Models\Concerns\HasResponsiveImages;
use App\Models\Concerns\Publishable;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The single profile row. The portrait is the `portrait` media collection.
 */
#[Fillable([
    'name', 'title', 'affiliation', 'short_bio', 'full_bio', 'expertise', 'key_facts',
    'contact_email_public', 'contact_note', 'status', 'published_at', 'sort_order',
])]
class Profile extends Model implements HasMedia
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory, HasResponsiveImages, InteractsWithMedia, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expertise' => 'array',
            'key_facts' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollections::PORTRAIT)
            ->useDisk(Disks::PUBLIC)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerResponsiveWebpConversions($media, [MediaCollections::PORTRAIT], responsive: true);
    }

    public function portrait(): ?Media
    {
        return $this->getFirstMedia(MediaCollections::PORTRAIT);
    }
}
