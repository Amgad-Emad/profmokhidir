<?php

namespace App\Models;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Models\Concerns\HasResponsiveImages;
use App\Models\Concerns\HasTranslatableContent;
use App\Models\Concerns\Publishable;
use Database\Factories\OtherItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Structured "Other information" items with a free-form `media` collection.
 */
#[Fillable([
    'title', 'slug', 'item_type', 'summary', 'body', 'date', 'category', 'destination_url',
    'status', 'published_at', 'sort_order',
])]
class OtherItem extends Model implements HasMedia
{
    /** @use HasFactory<OtherItemFactory> */
    use HasFactory, HasResponsiveImages, HasTranslatableContent, InteractsWithMedia, Publishable, SoftDeletes;

    /**
     * Language-specific columns (English and Arabic); the first is the primary one.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'summary', 'body'];

    /**
     * Columns folded into the search column (App\Support\SearchText).
     *
     * @var list<string>
     */
    protected array $searchColumns = ['title', 'summary'];

    protected string $orderDateColumn = 'date';

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollections::MEDIA)->useDisk(Disks::PUBLIC);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerResponsiveWebpConversions($media, [MediaCollections::MEDIA], responsive: true);
    }

    /**
     * An item has its own page when it carries a body and no destination link.
     */
    public function hasDetail(): bool
    {
        return filled($this->body) && blank($this->destination_url);
    }

    public function typeLabel(): ?string
    {
        return filled($this->item_type) ? Str::headline((string) $this->item_type) : null;
    }

    /**
     * @param  Builder<OtherItem>  $query
     */
    #[Scope]
    protected function inCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }
}
