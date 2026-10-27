<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ChatAttachmentKind;
use App\Constants\ChatAttachments;
use App\Constants\ChatCopy;
use App\Constants\ChatSettings;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\RateLimits;
use App\Constants\SettingKeys;
use App\Exceptions\ChatRateLimitException;
use App\Models\ChatMessage;
use App\Models\ChatUpload;
use App\Models\User;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use App\Repositories\Contracts\ChatUploadRepositoryInterface;
use App\Support\ChatAccess;
use App\Support\ChatImageSanitiser;
use App\Support\HeicConverter;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Files attached to chat messages. A member's file is first staged in the
 * temporary area (a ChatUpload owned by the member, its media on the private
 * disk, metadata stripped, HEIC converted to JPEG); ChatService::send then
 * claims the staged files inside its insert transaction, so a retried send
 * with the same client uuid never attaches anything twice. Every staged file
 * counts toward the member's send rate limit. Downloads are checked here on
 * every request, as every chat call is.
 */
class ChatAttachmentService
{
    /** Variants a download link may ask for. */
    public const string VARIANT_ORIGINAL = 'original';

    public const string VARIANT_THUMB = 'thumb';

    public const string VARIANT_MEDIUM = 'medium';

    public function __construct(
        private readonly ChatUploadRepositoryInterface $uploads,
        private readonly ChatMessageRepositoryInterface $messages,
        private readonly MediaService $media,
        private readonly SettingService $settings,
        private readonly RateLimiter $limiter,
        private readonly ChatImageSanitiser $sanitiser,
        private readonly HeicConverter $heic,
    ) {}

    // -- Settings -----------------------------------------------------------

    public function enabled(): bool
    {
        return $this->settings->get(SettingKeys::CHAT_UPLOADS_ENABLED) !== '0';
    }

    public function imageMaxMegabytes(): int
    {
        return $this->megabytes(SettingKeys::CHAT_IMAGE_MAX_MB, ChatAttachments::IMAGE_MAX_MB);
    }

    public function documentMaxMegabytes(): int
    {
        return $this->megabytes(SettingKeys::CHAT_DOCUMENT_MAX_MB, ChatAttachments::DOCUMENT_MAX_MB);
    }

    /**
     * What the composer needs: whether uploads are on, the limits, the accepted types and the endpoint.
     *
     * @return array<string, mixed>
     */
    public function clientConfig(): array
    {
        return [
            'enabled' => $this->enabled(),
            'maxFiles' => ChatAttachments::MAX_FILES,
            'imageMaxBytes' => $this->imageMaxMegabytes() * 1024 * 1024,
            'documentMaxBytes' => $this->documentMaxMegabytes() * 1024 * 1024,
            'imageMaxMb' => $this->imageMaxMegabytes(),
            'documentMaxMb' => $this->documentMaxMegabytes(),
            'imageExtensions' => array_keys(ChatAttachments::IMAGE_TYPES),
            'documentExtensions' => array_keys(ChatAttachments::DOCUMENT_TYPES),
            'accept' => ChatAttachments::accept(),
            'altMaxLength' => ChatAttachments::ALT_MAX_LENGTH,
            'uploadUrl' => route(ChatAttachments::ROUTE_UPLOAD),
            'discardUrl' => route(ChatAttachments::ROUTE_DISCARD, ['upload' => '__uuid__']),
            'types' => __(ChatCopy::ATTACH_TYPES, [
                'image' => (string) $this->imageMaxMegabytes(),
                'document' => (string) $this->documentMaxMegabytes(),
                'max' => (string) ChatAttachments::MAX_FILES,
            ]),
        ];
    }

    // -- Staging ------------------------------------------------------------

    /**
     * Put a validated file in the member's temporary area. Images lose their
     * metadata (EXIF location included) and HEIC becomes JPEG first.
     *
     * @throws AuthorizationException
     * @throws ChatRateLimitException
     * @throws InvalidArgumentException when uploads are off or the member already has a full tray
     */
    public function stage(User $member, UploadedFile $file): ChatUpload
    {
        ChatAccess::guard($member);

        if (! $this->enabled()) {
            throw new InvalidArgumentException(__(ChatCopy::UPLOADS_OFF));
        }

        if ($this->uploads->countPendingFor((int) $member->getKey()) >= ChatAttachments::MAX_FILES) {
            throw new InvalidArgumentException(__(ChatCopy::ATTACH_TOO_MANY, ['max' => (string) ChatAttachments::MAX_FILES]));
        }

        $this->hitRateLimit($member);

        $extension = strtolower($file->getClientOriginalExtension());
        $kind = ChatAttachments::kindFor($extension) ?? throw new InvalidArgumentException(__(ChatCopy::ATTACH_UNSUPPORTED, ['name' => $file->getClientOriginalName()]));
        $displayName = $this->displayName($file->getClientOriginalName(), $extension);

        $directory = storage_path('app/private/chat-staging/'.Str::uuid());
        File::ensureDirectoryExists($directory);
        $path = $directory.'/'.Str::slug(pathinfo($displayName, PATHINFO_FILENAME)).'.'.$extension;
        File::copy((string) $file->getRealPath(), $path);

        try {
            if (in_array($extension, ChatAttachments::HEIC_EXTENSIONS, true)) {
                $path = $this->heic->toJpeg($path);
                $extension = 'jpg';
                $displayName = pathinfo($displayName, PATHINFO_FILENAME).'.jpg';
            }

            $mime = $kind === ChatAttachmentKind::Image
                ? ChatAttachments::IMAGE_TYPES[$extension][0]
                : ChatAttachments::DOCUMENT_MIME[$extension];

            if ($kind === ChatAttachmentKind::Image) {
                $this->sanitiser->clean($path, $extension);
            }

            $upload = $this->uploads->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $member->getKey(),
                'kind' => $kind->value,
                'original_name' => $displayName,
                'extension' => $extension,
                'mime_type' => $mime,
                'size' => (int) filesize($path),
            ]);

            try {
                $this->media->attach($upload, $path, MediaCollections::CHAT_PENDING, [], Disks::PRIVATE);
            } catch (Throwable $exception) {
                $this->uploads->delete($upload);

                throw $exception;
            }

            return $upload->load('media');
        } catch (RuntimeException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), 0, $exception);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    /**
     * Remove one of the member's staged files (the × on a preview).
     */
    public function discard(User $member, string $uuid): bool
    {
        $upload = $this->uploads->findPendingFor((int) $member->getKey(), $uuid);

        if ($upload === null) {
            return false;
        }

        $this->deleteUpload($upload);

        return true;
    }

    /**
     * The shape of a staged file as the composer shows it.
     *
     * @return array{id: string, kind: string, name: string, size: int}
     */
    public function presentUpload(ChatUpload $upload): array
    {
        return [
            'id' => (string) $upload->uuid,
            'kind' => $upload->kind->value,
            'name' => (string) $upload->original_name,
            'size' => (int) $upload->size,
        ];
    }

    // -- Sending ------------------------------------------------------------

    /**
     * Check the attachments named in a send before anything is written: at
     * most MAX_FILES, each the member's own staged file. A description is
     * optional. Returns them as [uuid => description, '' when none was given].
     *
     * @param  list<array{id?: mixed, alt?: mixed}>|array<int, mixed>  $attachments
     * @return array<string, string>
     *
     * @throws InvalidArgumentException
     */
    public function prepare(User $member, array $attachments): array
    {
        if ($attachments === []) {
            return [];
        }

        if (! $this->enabled()) {
            throw new InvalidArgumentException(__(ChatCopy::UPLOADS_OFF));
        }

        if (count($attachments) > ChatAttachments::MAX_FILES) {
            throw new InvalidArgumentException(__(ChatCopy::ATTACH_TOO_MANY, ['max' => (string) ChatAttachments::MAX_FILES]));
        }

        $prepared = [];

        foreach ($attachments as $attachment) {
            $uuid = is_array($attachment) ? (string) ($attachment['id'] ?? '') : '';
            $alt = is_array($attachment) ? trim((string) ($attachment['alt'] ?? '')) : '';

            if (! Str::isUuid($uuid) || array_key_exists($uuid, $prepared)) {
                throw new InvalidArgumentException(__('One of the files could not be found. Remove it and attach it again.'));
            }

            $this->uploads->findPendingFor((int) $member->getKey(), $uuid)
                ?? throw new InvalidArgumentException(__('One of the files is no longer available. Remove it and attach it again.'));

            $prepared[$uuid] = mb_substr($alt, 0, ChatAttachments::ALT_MAX_LENGTH);
        }

        return $prepared;
    }

    /**
     * Move the staged files onto a just-created message. Runs inside the send
     * transaction: the uploads are locked, so a file can only ever be claimed
     * once, and a failure rolls the message back with it. An image sent
     * without a description is stored with alt text naming who shared it,
     * so a screen reader still has something to say.
     *
     * @param  array<string, string>  $prepared  uuid => description, from prepare()
     *
     * @throws InvalidArgumentException when a file was claimed or removed in the meantime
     */
    public function claim(ChatMessage $message, User $member, array $prepared): void
    {
        if ($prepared === []) {
            return;
        }

        $uploads = $this->uploads->lockPendingFor((int) $member->getKey(), array_keys($prepared));

        if ($uploads->count() !== count($prepared)) {
            throw new InvalidArgumentException(__('One of the files is no longer available. Remove it and attach it again.'));
        }

        $now = CarbonImmutable::now();

        foreach ($uploads->values() as $index => $upload) {
            /** @var ChatUpload $upload */
            $media = $upload->getFirstMedia(MediaCollections::CHAT_PENDING)
                ?? throw new InvalidArgumentException(__('One of the files is no longer available. Remove it and attach it again.'));

            $properties = $upload->isImage() ? [MediaProperties::ALT_TEXT => $this->altText($prepared[$upload->uuid], $member)] : [];
            $media->order_column = $index + 1;
            $this->media->reassign($media, $message, MediaCollections::CHAT_ATTACHMENTS, $properties);
            $this->uploads->markClaimed($upload, (int) $message->getKey(), $now);
        }

        $message->unsetRelation('media');
    }

    /**
     * Delete a message's files (the message itself is soft-deleted by ChatService).
     */
    public function purge(ChatMessage $message): void
    {
        foreach ($message->getMedia(MediaCollections::CHAT_ATTACHMENTS) as $media) {
            $this->media->remove($media);
        }

        $message->unsetRelation('media');
    }

    /**
     * Remove staged files nobody sent within TEMPORARY_TTL_HOURS. Returns how many were removed.
     */
    public function prune(?CarbonImmutable $now = null): int
    {
        $before = ($now ?? CarbonImmutable::now())->subHours(ChatAttachments::TEMPORARY_TTL_HOURS);
        $removed = 0;

        do {
            $batch = $this->uploads->unclaimedBefore($before);

            foreach ($batch as $upload) {
                $this->deleteUpload($upload);
                $removed++;
            }
        } while ($batch->isNotEmpty());

        return $removed;
    }

    // -- Thumbnails ---------------------------------------------------------

    /**
     * A queued conversion finished (RefreshChatThumbnails). When it is the
     * thumbnail of a sent message's image, the message is marked changed so
     * every open room picks up the thumbnail on its next poll. Returns
     * whether a message was marked.
     */
    public function conversionCompleted(Media $media, string $conversion): bool
    {
        $message = $media->model;

        if ($conversion !== ChatAttachments::CONVERSION_THUMB
            || $media->collection_name !== MediaCollections::CHAT_ATTACHMENTS
            || ! str_starts_with((string) $media->mime_type, 'image/')
            || ! $message instanceof ChatMessage) {
            return false;
        }

        $this->messages->markChanged($message);

        return true;
    }

    // -- Downloads ----------------------------------------------------------

    /**
     * Resolve a download: only files attached to a live message, only for
     * approved members and moderators (checked again on every request).
     *
     * @return array{disk: string, path: string, name: string, mime: string, inline: bool}
     *
     * @throws AuthorizationException
     */
    public function download(User $viewer, int $mediaId, string $variant): array
    {
        if (! ChatAccess::canModerate($viewer)) {
            ChatAccess::guard($viewer);
        }

        $media = $this->media->find($mediaId);
        $message = $media?->model;

        if ($media === null || $media->collection_name !== MediaCollections::CHAT_ATTACHMENTS || ! $message instanceof ChatMessage || $message->trashed()) {
            abort(404);
        }

        $image = str_starts_with((string) $media->mime_type, 'image/');
        $conversion = match ($variant) {
            self::VARIANT_THUMB => ChatAttachments::CONVERSION_THUMB,
            self::VARIANT_MEDIUM => ChatAttachments::CONVERSION_MEDIUM,
            default => null,
        };

        if ($conversion !== null && $image && $media->hasGeneratedConversion($conversion)) {
            return [
                'disk' => $media->conversions_disk ?: $media->disk,
                'path' => $media->getPathRelativeToRoot($conversion),
                'name' => pathinfo($media->file_name, PATHINFO_FILENAME).'.webp',
                'mime' => 'image/webp',
                'inline' => true,
            ];
        }

        return [
            'disk' => $media->disk,
            'path' => $media->getPathRelativeToRoot(),
            'name' => $media->file_name,
            'mime' => (string) $media->mime_type,
            // Images open in the page; documents always download.
            'inline' => $image && $variant !== self::VARIANT_ORIGINAL,
        ];
    }

    /**
     * The alt text an image is stored with: the member's description, or "Image shared by {name}".
     */
    public function altText(string $description, User $member): string
    {
        $description = trim($description);

        return $description !== ''
            ? mb_substr($description, 0, ChatAttachments::ALT_MAX_LENGTH)
            : mb_substr(strtr(ChatCopy::ATTACH_DEFAULT_ALT, [':name' => (string) $member->name]), 0, ChatAttachments::ALT_MAX_LENGTH);
    }

    private function deleteUpload(ChatUpload $upload): void
    {
        foreach ($upload->getMedia(MediaCollections::CHAT_PENDING) as $media) {
            $this->media->remove($media);
        }

        $this->uploads->delete($upload);
    }

    /**
     * @throws ChatRateLimitException
     */
    private function hitRateLimit(User $member): void
    {
        $key = RateLimits::chatSendKey((int) $member->getKey());

        if ($this->limiter->tooManyAttempts($key, ChatSettings::SEND_LIMIT_PER_MINUTE)) {
            throw ChatRateLimitException::make($this->limiter->availableIn($key));
        }

        $this->limiter->hit($key, 60);
    }

    private function megabytes(string $key, int $default): int
    {
        $value = (int) ($this->settings->get($key) ?? $default);

        return $value >= ChatAttachments::LIMIT_MIN_MB && $value <= ChatAttachments::LIMIT_MAX_MB ? $value : $default;
    }

    private function displayName(string $original, string $extension): string
    {
        $base = trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', ' ', pathinfo($original, PATHINFO_FILENAME)));

        return mb_substr($base !== '' ? $base : 'file', 0, 120).'.'.$extension;
    }
}
