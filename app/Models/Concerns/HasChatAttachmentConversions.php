<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Constants\ChatAttachments;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaConversions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Chat attachments live on the private disk. Images get two queued WebP
 * conversions (a grid thumbnail and a medium size for the lightbox); documents
 * have no image generator, so they get none. The same
 * collections and conversions are registered on the temporary upload and on
 * the message, because a staged file's media row is moved from one to the
 * other when the message is sent (the files themselves stay where they are).
 */
trait HasChatAttachmentConversions
{
    public function registerMediaCollections(): void
    {
        foreach ([MediaCollections::CHAT_PENDING, MediaCollections::CHAT_ATTACHMENTS] as $collection) {
            $this->addMediaCollection($collection)->useDisk(Disks::PRIVATE);
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach ([ChatAttachments::CONVERSION_THUMB => ChatAttachments::THUMB_WIDTH, ChatAttachments::CONVERSION_MEDIUM => ChatAttachments::MEDIUM_WIDTH] as $name => $width) {
            $this->addMediaConversion($name)
                ->format(MediaConversions::FORMAT)
                ->width($width)
                ->queued()
                ->performOnCollections(MediaCollections::CHAT_PENDING, MediaCollections::CHAT_ATTACHMENTS);
        }
    }
}
