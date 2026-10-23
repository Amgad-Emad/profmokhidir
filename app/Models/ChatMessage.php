<?php

namespace App\Models;

use App\Constants\ChatSettings;
use App\Constants\MediaCollections;
use App\Models\Concerns\HasChatAttachmentConversions;
use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * `id` is the only ordering key. (user_id, client_uuid) is unique so retried
 * sends are idempotent; `version` guards edits with optimistic locking.
 * `reply_to_id` points at the message answered (looked up live, deleted or
 * not); `pinned_at` / `pinned_by` record a moderator's pin. Attached files are
 * the `chat_attachments` media collection on the private disk.
 */
#[Fillable(['channel_id', 'user_id', 'client_uuid', 'body', 'reply_to_id', 'version', 'edited_at', 'pinned_at', 'pinned_by'])]
class ChatMessage extends Model implements HasMedia
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasChatAttachmentConversions, HasFactory, InteractsWithMedia, SoftDeletes {
        HasChatAttachmentConversions::registerMediaCollections insteadof InteractsWithMedia;
        HasChatAttachmentConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

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

    /**
     * @return MediaCollection<int, Media>
     */
    public function attachments(): MediaCollection
    {
        return $this->getMedia(MediaCollections::CHAT_ATTACHMENTS);
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
