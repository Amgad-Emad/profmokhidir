<?php

namespace App\Models;

use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\PageBlockType;
use App\Models\Concerns\HasResponsiveImages;
use App\Models\Concerns\HasTranslatableContent;
use Database\Factories\PageBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One editable slot of a page (App\Constants\PageBlocks). `value` is what
 * visitors see once the page is published; `draft_value` holds the editor's
 * change until it is published (null when there is none). Both are per
 * language ({"en": …, "ar": …}); an Arabic image, link or search value holds
 * only its words, the image and destination being the English one's. Image and share
 * image blocks keep their uploads in the `page_images` collection; the value
 * names the one in use, so a draft image never shows before it is published.
 */
#[Fillable(['page_id', 'key', 'type', 'value', 'draft_value', 'sort_order'])]
class PageBlock extends Model implements HasMedia
{
    /** @use HasFactory<PageBlockFactory> */
    use HasFactory, HasResponsiveImages, HasTranslatableContent, InteractsWithMedia;

    /**
     * Language-specific columns (English and Arabic); the first is the primary one.
     *
     * @var list<string>
     */
    public array $translatable = ['value', 'draft_value'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PageBlockType::class,
            'value' => 'array',
            'draft_value' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Whether an unpublished change waits (in any language).
     */
    public function hasDraft(): bool
    {
        return ($this->getAttributes()['draft_value'] ?? null) !== null && $this->getTranslations('draft_value') !== [];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollections::PAGE_IMAGES)
            ->useDisk(Disks::PUBLIC)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerResponsiveWebpConversions($media, [MediaCollections::PAGE_IMAGES], responsive: true);
    }
}
