<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Names of the spatie/laravel-medialibrary collections on each model.
 */
final class MediaCollections
{
    /** Profile: the single portrait. */
    public const string PORTRAIT = 'portrait';

    /** Publication / TeachingMaterial: the single optional file. */
    public const string FILE = 'file';

    /** Video: the single optional uploaded video file. */
    public const string VIDEO = 'video';

    /** Video: the single poster image. */
    public const string THUMBNAIL = 'thumbnail';

    /** Album: ordered photos. */
    public const string PHOTOS = 'photos';

    /** OtherItem: free-form images or files. */
    public const string MEDIA = 'media';

    /** PageBlock: the hero and share images of an editable page (the block's value names the one in use). */
    public const string PAGE_IMAGES = 'page_images';

    /** ChatUpload: a member's file waiting, in the temporary area, to be sent with a message. */
    public const string CHAT_PENDING = 'chat_pending';

    /** ChatMessage: the files sent with the message (private disk, signed downloads). */
    public const string CHAT_ATTACHMENTS = 'chat_attachments';

    /**
     * Collections whose files are images and receive the responsive WebP conversions.
     *
     * @return list<string>
     */
    public static function images(): array
    {
        return [self::PORTRAIT, self::THUMBNAIL, self::PHOTOS, self::MEDIA, self::PAGE_IMAGES];
    }
}
