<?php

namespace App\Models;

use App\Constants\Pages;
use App\Models\Concerns\Publishable;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One public page whose words and images editors change (App\Constants\Pages).
 * While it is a draft, visitors see the built-in copy; once published, its
 * blocks. Its version history is the shared revisions table.
 */
#[Fillable(['key', 'status', 'published_at', 'sort_order', 'hidden_sections'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['hidden_sections' => 'array'];
    }

    /**
     * The section headings (PageBlocks) visitors do not see.
     *
     * @return list<string>
     */
    public function hiddenSections(): array
    {
        return array_values(array_filter((array) $this->hidden_sections, 'is_string'));
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('sort_order');
    }

    /**
     * The page's name in the dashboard.
     */
    public function title(): string
    {
        return Pages::isValid((string) $this->key) ? Pages::get((string) $this->key)['title'] : (string) $this->key;
    }
}
