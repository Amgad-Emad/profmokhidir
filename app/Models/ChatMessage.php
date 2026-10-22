<?php

namespace App\Models;

use App\Constants\ChatSettings;
use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * `id` is the only ordering key. (user_id, client_uuid) is unique so retried
 * sends are idempotent; `version` guards edits with optimistic locking.
 * `reply_to_id` points at the message answered (looked up live, deleted or
 * not); `pinned_at` / `pinned_by` record a moderator's pin.
 */
#[Fillable(['channel_id', 'user_id', 'client_uuid', 'body', 'reply_to_id', 'version', 'edited_at', 'pinned_at', 'pinned_by'])]
class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory, SoftDeletes;

    public const int BODY_MAX_LENGTH = ChatSettings::BODY_MAX_LENGTH;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'edited_at' => 'datetime',
            'pinned_at' => 'datetime',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChatChannel::class, 'channel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The message this one answers, deleted or not (a removed original is
     * shown as a placeholder, never dropped).
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id')->withTrashed();
    }

    public function pinnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ChatReport::class, 'message_id');
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    public function isPinned(): bool
    {
        return $this->pinned_at !== null;
    }
}
