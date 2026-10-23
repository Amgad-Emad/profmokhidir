<?php

namespace App\Models;

use App\Constants\ChatAttachmentKind;
use App\Models\Concerns\HasChatAttachmentConversions;
use Database\Factories\ChatUploadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A file a member has uploaded to the temporary area, waiting to be sent with
 * a message. `uuid` is the only identifier the browser sees. Once claimed by
 * a message (message_id, claimed_at) its media row belongs to that message.
 */
#[Fillable(['uuid', 'user_id', 'kind', 'original_name', 'extension', 'mime_type', 'size', 'message_id', 'claimed_at'])]
class ChatUpload extends Model implements HasMedia
{
    /** @use HasFactory<ChatUploadFactory> */
    use HasChatAttachmentConversions, HasFactory, InteractsWithMedia {
        HasChatAttachmentConversions::registerMediaCollections insteadof InteractsWithMedia;
        HasChatAttachmentConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ChatAttachmentKind::class,
            'size' => 'integer',
            'claimed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'message_id')->withTrashed();
    }

    public function isImage(): bool
    {
        return $this->kind === ChatAttachmentKind::Image;
    }

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }
}
