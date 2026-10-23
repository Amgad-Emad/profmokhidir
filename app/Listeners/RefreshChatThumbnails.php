<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\ChatAttachmentService;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompletedEvent;

/**
 * When a chat image's thumbnail has been made (a queued conversion), marks its
 * message as changed so open rooms swap the full-size original they were
 * showing for the thumbnail on their next poll.
 */
class RefreshChatThumbnails
{
    public function __construct(private readonly ChatAttachmentService $attachments) {}

    public function handle(ConversionHasBeenCompletedEvent $event): void
    {
        $this->attachments->conversionCompleted($event->media, $event->conversion->getName());
    }
}
