<?php

namespace App\Models;

use App\Constants\Catalogue;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\PublicationType;
use App\Models\Concerns\HasTranslatableContent;
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
    'is_featured', 'topic', 'status', 'published_at', 'sort_order',
])]
class Publication extends Model implements HasMedia
{
    /** @use HasFactory<PublicationFactory> */
    use HasFactory, HasTranslatableContent, InteractsWithMedia, Publishable, SoftDeletes;

    /**
     * Language-specific columns (English and Arabic); the first is the primary one.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'abstract'];

    /**
     * Columns folded into the search column (App\Support\SearchText).
     *
     * @var list<string>
     */
    protected array $searchColumns = ['title', 'authors', 'venue_publisher', 'abstract'];

    protected string $orderDateColumn = 'year';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PublicationType::class,
            'year' => 'integer',
            'is_featured' => 'boolean',
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

    /**
     * Marked by an editor for the "Selected works" strip.
     *
     * @param  Builder<Publication>  $query
     */
    #[Scope]
    protected function featured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * @param  Builder<Publication>  $query
     */
    #[Scope]
    protected function onTopic(Builder $query, string $topic): void
    {
        $query->where('topic', $topic);
    }

    /**
     * Published between two years, inclusive; either end may be open.
     *
     * @param  Builder<Publication>  $query
     */
    #[Scope]
    protected function betweenYears(Builder $query, ?int $from, ?int $to): void
    {
        $query
            ->when($from !== null, fn (Builder $query) => $query->where('year', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->where('year', '<=', $to));
    }

    /**
     * The decade a publication belongs to (1978 => 1970), or null when undated.
     */
    public function decade(): ?int
    {
        return $this->year === null ? null : intdiv($this->year, Catalogue::DECADE) * Catalogue::DECADE;
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
