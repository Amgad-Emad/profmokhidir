<?php

namespace App\Models;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\TeachingAccess;
use App\Constants\TeachingMaterialType;
use App\Models\Concerns\HasTranslatableContent;
use App\Models\Concerns\Publishable;
use Database\Factories\TeachingMaterialFactory;
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
 * The optional `file` collection. Members-only materials keep their file on the
 * private disk; the disk is chosen by the service from `access` when attaching.
 */
#[Fillable([
    'title', 'course', 'course_description', 'topic', 'type', 'description', 'version', 'material_date', 'external_url', 'access',
    'status', 'published_at', 'sort_order',
])]
class TeachingMaterial extends Model implements HasMedia
{
    /** @use HasFactory<TeachingMaterialFactory> */
    use HasFactory, HasTranslatableContent, InteractsWithMedia, Publishable, SoftDeletes;

    /**
     * Language-specific columns (English and Arabic); the first is the primary one.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'course_description', 'description'];

    /**
     * Columns folded into the search column (App\Support\SearchText).
     *
     * @var list<string>
     */
    protected array $searchColumns = ['title', 'course', 'topic', 'description'];

    protected string $orderDateColumn = 'material_date';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TeachingMaterialType::class,
            'access' => TeachingAccess::class,
            'material_date' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        // Default disk is public; the service passes Disks::PRIVATE for members-only files.
        $this->addMediaCollection(MediaCollections::FILE)->useDisk(Disks::PUBLIC)->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Teaching files are documents; no image conversions.
    }

    /**
     * @param  Builder<TeachingMaterial>  $query
     */
    #[Scope]
    protected function withAccess(Builder $query, TeachingAccess ...$access): void
    {
        $query->whereIn('access', array_map(fn (TeachingAccess $a): string => $a->value, $access));
    }

    public function file(): ?Media
    {
        return $this->getFirstMedia(MediaCollections::FILE);
    }

    public function isMembersOnly(): bool
    {
        return $this->access === TeachingAccess::Members;
    }

    public function fileDisk(): string
    {
        return $this->access->disk();
    }
}
