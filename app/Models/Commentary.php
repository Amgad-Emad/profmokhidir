<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Database\Factories\CommentaryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title', 'slug', 'date', 'summary', 'body', 'external_url', 'tags', 'byline',
    'status', 'published_at', 'sort_order',
])]
class Commentary extends Model
{
    /** @use HasFactory<CommentaryFactory> */
    use HasFactory, Publishable;

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
