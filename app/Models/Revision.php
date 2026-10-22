<?php

namespace App\Models;

use App\Constants\RevisionKind;
use Database\Factories\RevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One saved version of a content record's form values. Append-only, so there
 * is no updated_at.
 */
#[Fillable(['revisionable_type', 'revisionable_id', 'user_id', 'kind', 'data', 'created_at'])]
class Revision extends Model
{
    /** @use HasFactory<RevisionFactory> */
    use HasFactory;

    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => RevisionKind::class,
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }
}
