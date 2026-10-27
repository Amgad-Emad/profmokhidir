<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableContent;
use App\Models\Concerns\Publishable;
use Database\Factories\CommentaryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title', 'slug', 'date', 'summary', 'epigraph', 'epigraph_attribution', 'body', 'external_url', 'tags', 'byline',
    'status', 'published_at', 'sort_order',
])]
class Commentary extends Model
{
    /** @use HasFactory<CommentaryFactory> */
    use HasFactory, HasTranslatableContent, Publishable, SoftDeletes;

    /**
     * Language-specific columns (English and Arabic); the first is the primary one.
     *
     * @var list<string>
     */
    public array $translatable = ['title', 'summary', 'epigraph', 'body'];

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
        return [
            'date' => 'date',
            'tags' => 'array',
        ];
    }

    /**
     * @param  Builder<Commentary>  $query
     */
    #[Scope]
    protected function tagged(Builder $query, string $tag): void
    {
        $query->whereJsonContains('tags', $tag);
    }
}
