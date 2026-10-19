<?php

namespace App\Models;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\PublicationType;
use App\Models\Concerns\Publishable;
use Database\Factories\PublicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The optional full-text file is the single-file `file` media collection.
 */
#[Fillable([
    'title', 'slug', 'type', 'authors', 'year', 'venue_publisher', 'doi', 'citation', 'abstract', 'url',
    'status', 'published_at', 'sort_order',
])]
class Publication extends Model implements HasMedia
{
    /** @use HasFactory<PublicationFactory> */
    use HasFactory, InteractsWithMedia, Publishable, SoftDeletes;

    protected string $orderDateColumn = 'year';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PublicationType::class,
            'year' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollections::FILE)->useDisk(Disks::PUBLIC)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Publication files are documents; no image conversions.
    }

    /**
     * @param  Builder<Publication>  $query
     */
    #[Scope]
    protected function ofType(Builder $query, PublicationType $type): void
    {
        $query->where('type', $type->value);
    }

    /**
     * @param  Builder<Publication>  $query
     */
    #[Scope]
    protected function inYear(Builder $query, int $year): void
    {
        $query->where('year', $year);
    }

    public function file(): ?Media
    {
        return $this->getFirstMedia(MediaCollections::FILE);
    }

    public function doiUrl(): ?string
    {
        return $this->doi ? 'https://doi.org/'.ltrim($this->doi, '/') : null;
    }
}
