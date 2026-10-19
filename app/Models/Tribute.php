<?php

namespace App\Models;

use App\Constants\TributeRelationship;
use App\Constants\TributeStatus;
use Database\Factories\TributeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Visitor tributes, shown only once approved. `email` and `ip_hash` are hidden
 * from serialisation and must never be rendered.
 */
#[Fillable([
    'name', 'relationship', 'location', 'message', 'email', 'status',
    'approved_by', 'approved_at', 'ip_hash', 'published_at', 'sort_order',
])]
#[Hidden(['email', 'ip_hash'])]
class Tribute extends Model
{
    /** @use HasFactory<TributeFactory> */
    use HasFactory;

    public const int MESSAGE_MAX_LENGTH = 2000;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relationship' => TributeRelationship::class,
            'status' => TributeStatus::class,
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approved tributes are the "published" ones, so the shared repository helpers apply.
     *
     * @param  Builder<Tribute>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', TributeStatus::Approved->value);
    }

    /**
     * @param  Builder<Tribute>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, TributeStatus $status): void
    {
        $query->where('status', $status->value);
    }

    /**
     * @param  Builder<Tribute>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderByDesc('approved_at')->orderByDesc('id');
    }

    public function isApproved(): bool
    {
        return $this->status === TributeStatus::Approved;
    }

    public function isPending(): bool
    {
        return $this->status === TributeStatus::Pending;
    }
}
