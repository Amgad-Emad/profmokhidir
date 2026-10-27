<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Keys of the site settings table, edited on the admin "Site settings"
 * screen. Public contact details, the address that receives staff
 * notifications, and the mail sender. The notification and contact code reads
 * these through SettingService, never from config or constants directly.
 */
final class SettingKeys
{
    public const string CONTACT_EMAIL = 'contact_email';

    public const string CONTACT_PHONE = 'contact_phone';

    public const string CONTACT_ADDRESS = 'contact_address';

    public const string NOTIFICATION_EMAIL = 'notification_email';

    public const string MAIL_FROM_NAME = 'mail_from_name';

    public const string MAIL_FROM_ADDRESS = 'mail_from_address';

    /** Community chat uploads: on ("1") or off ("0"), and the size limits in megabytes. */
    public const string CHAT_UPLOADS_ENABLED = 'chat_uploads_enabled';

    public const string CHAT_IMAGE_MAX_MB = 'chat_image_max_mb';

    public const string CHAT_DOCUMENT_MAX_MB = 'chat_document_max_mb';

    /** First-party, cookieless visitor analytics: off ("0") until the client approves it, then on ("1"). */
    public const string ANALYTICS_ENABLED = 'analytics_enabled';

    /** Arabic pages: running-text numbers in Western ("latin", the default) or Arabic-Indic digits ("arabic"). Years, citations, DOIs and data always use Western digits. */
    public const string ARABIC_DIGITS = 'arabic_digits';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CONTACT_EMAIL,
            self::CONTACT_PHONE,
            self::CONTACT_ADDRESS,
            self::NOTIFICATION_EMAIL,
            self::MAIL_FROM_NAME,
            self::MAIL_FROM_ADDRESS,
            self::CHAT_UPLOADS_ENABLED,
            self::CHAT_IMAGE_MAX_MB,
            self::CHAT_DOCUMENT_MAX_MB,
            self::ANALYTICS_ENABLED,
            self::ARABIC_DIGITS,
        ];
    }

    /**
     * Keys of the community chat uploads.
     *
     * @return list<string>
     */
    public static function chatUploads(): array
    {
        return [self::CHAT_UPLOADS_ENABLED, self::CHAT_IMAGE_MAX_MB, self::CHAT_DOCUMENT_MAX_MB];
    }

    /**
     * Keys shown on the public site.
     *
     * @return list<string>
     */
    public static function contact(): array
    {
        return [self::CONTACT_EMAIL, self::CONTACT_PHONE, self::CONTACT_ADDRESS];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::CONTACT_EMAIL => __('Public contact email'),
            self::CONTACT_PHONE => __('Public contact phone'),
            self::CONTACT_ADDRESS => __('Public postal address'),
            self::NOTIFICATION_EMAIL => __('Notification address'),
            self::MAIL_FROM_NAME => __('Mail sender name'),
            self::MAIL_FROM_ADDRESS => __('Mail sender address'),
            self::CHAT_UPLOADS_ENABLED => __('Members may attach files'),
            self::CHAT_IMAGE_MAX_MB => __('Largest image (MB)'),
            self::CHAT_DOCUMENT_MAX_MB => __('Largest document (MB)'),
            self::ANALYTICS_ENABLED => __('Record visitor analytics'),
            self::ARABIC_DIGITS => __('Digits on Arabic pages'),
        ];
    }

    public static function isValid(string $key): bool
    {
        return in_array($key, self::all(), true);
    }
}
