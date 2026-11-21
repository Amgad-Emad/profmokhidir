<?php

namespace App\Models;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Models\Concerns\HasResponsiveImages;
use App\Models\Concerns\HasTranslatableContent;
use App\Models\Concerns\Publishable;
use Database\Factories\AwardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['name', 'organisation', 'year', 'description', 'source_url', 'status', 'published_at', 'sort_order'])]
class Award extends Model implements HasMedia
{
    /** @use HasFactory<AwardFactory> */
    use HasFactory, HasResponsiveImages, HasTranslatableContent, InteractsWithMedia, Publishable, SoftDeletes;

    /**
     * Language-specific columns (English and Arabic); the first is the primary one.
     *
     * @var list<string>
     */
    public array $translatable = ['name', 'organisation', 'description'];

    /**
     * Columns folded into the search column (App\Support\SearchText).
     *
     * @var list<string>
     */
    protected array $searchColumns = ['name', 'organisation', 'description'];

    protected string $orderDateColumn = 'year';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['year' => 'integer'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollections::AWARD_IMAGES)->useDisk(Disks::PUBLIC);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerResponsiveWebpConversions($media, [MediaCollections::AWARD_IMAGES], responsive: true);
    }
}
