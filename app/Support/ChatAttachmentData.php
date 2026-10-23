<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\ChatAttachments;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Models\ChatMessage;
use App\Services\ChatAttachmentService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Number;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The files of a chat message as the browser receives them: images (with
 * their description as alt text, a thumbnail and a medium size for the
 * lightbox) and documents (type, name, size). Every link is a signed,
 * expiring route that checks the viewer's chat access again.
 */
final class ChatAttachmentData
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function collection(ChatMessage $message): array
    {
        if ($message->trashed()) {
            return [];
        }

        return $message->getMedia(MediaCollections::CHAT_ATTACHMENTS)
            ->map(fn (Media $media): array => self::from($media))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function from(Media $media): array
    {
        $extension = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));
        $image = str_starts_with((string) $media->mime_type, 'image/');

        return [
            'id' => (int) $media->getKey(),
            'kind' => $image ? 'image' : 'document',
            'name' => $media->file_name,
            'type' => ChatAttachments::typeLabel($extension),
            'extension' => $extension,
            'size' => (int) $media->size,
            'size_label' => Number::fileSize((int) $media->size, maxPrecision: 1),
            'alt' => $image ? (string) $media->getCustomProperty(MediaProperties::ALT_TEXT, '') : '',
            'width' => $image ? $media->getCustomProperty(MediaProperties::WIDTH) : null,
            'height' => $image ? $media->getCustomProperty(MediaProperties::HEIGHT) : null,
            'url' => self::url($media, ChatAttachmentService::VARIANT_ORIGINAL),
            'thumb' => $image ? self::url($media, ChatAttachmentService::VARIANT_THUMB) : null,
            'medium' => $image ? self::url($media, ChatAttachmentService::VARIANT_MEDIUM) : null,
        ];
    }

    private static function url(Media $media, string $variant): string
    {
        return URL::temporarySignedRoute(
            ChatAttachments::ROUTE_DOWNLOAD,
            now()->addMinutes(ChatAttachments::URL_TTL_MINUTES),
            ['media' => $media->getKey(), 'variant' => $variant],
        );
    }
}
