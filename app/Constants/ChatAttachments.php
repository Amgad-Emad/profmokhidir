<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Files members attach to chat messages. The limits below are the defaults;
 * administrators can lower or raise the size limits and switch uploads off in
 * Site settings (SettingKeys::CHAT_*), read through ChatAttachmentService.
 * Types are checked by content (MIME sniffing) as well as by extension.
 */
final class ChatAttachments
{
    /** Most files attached to one message. */
    public const int MAX_FILES = 5;

    /** Default size limits, in megabytes. */
    public const int IMAGE_MAX_MB = 10;

    public const int DOCUMENT_MAX_MB = 25;

    /** Bounds an administrator may choose for either limit, in megabytes. */
    public const int LIMIT_MIN_MB = 1;

    public const int LIMIT_MAX_MB = 100;

    /** Longest image description (used as the alt text). */
    public const int ALT_MAX_LENGTH = 200;

    /** Unclaimed temporary uploads are removed by the scheduler after this many hours. */
    public const int TEMPORARY_TTL_HOURS = 24;

    /** How long a signed download link stays valid, in minutes. */
    public const int URL_TTL_MINUTES = 720;

    /** Route names: staging an upload, discarding it, downloading a stored attachment. */
    public const string ROUTE_UPLOAD = 'community.chat.uploads.store';

    public const string ROUTE_DISCARD = 'community.chat.uploads.destroy';

    public const string ROUTE_DOWNLOAD = 'community.chat.attachments.show';

    /** Queued WebP conversions of image attachments: a grid thumbnail and a medium size for the lightbox. */
    public const string CONVERSION_THUMB = 'chat-thumb';

    public const string CONVERSION_MEDIUM = 'chat-medium';

    public const int THUMB_WIDTH = 480;

    public const int MEDIUM_WIDTH = 1280;

    /** Re-encoding quality for JPEG and WebP images whose metadata is stripped. */
    public const int IMAGE_QUALITY = 88;

    /**
     * Image types by extension, with the MIME types their content may sniff as.
     * HEIC and HEIF are converted to JPEG when they are staged.
     *
     * @var array<string, list<string>>
     */
    public const array IMAGE_TYPES = [
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'heic' => ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'],
        'heif' => ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'],
    ];

    /** @var list<string> */
    public const array HEIC_EXTENSIONS = ['heic', 'heif'];

    /** Office Open XML files are ZIP archives; their content must also hold this part. */
    public const string OOXML_MARKER = '[Content_Types].xml';

    /** @var list<string> */
    public const array OOXML_EXTENSIONS = ['docx', 'xlsx', 'pptx'];

    /** Legacy Office binaries sniff as a compound document. */
    private const array OLE = ['application/x-ole-storage', 'application/cdfv2', 'application/vnd.ms-office', 'application/octet-stream'];

    /** What an OOXML file may sniff as before its ZIP content is checked. */
    private const array OOXML_SNIFFS = ['application/zip', 'application/octet-stream'];

    /**
     * Document types by extension, with the MIME types their content may sniff as.
     *
     * @var array<string, list<string>>
     */
    public const array DOCUMENT_TYPES = [
        'pdf' => ['application/pdf', 'application/x-pdf'],
        'doc' => ['application/msword', ...self::OLE],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', ...self::OOXML_SNIFFS],
        'xls' => ['application/vnd.ms-excel', ...self::OLE],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', ...self::OOXML_SNIFFS],
        'ppt' => ['application/vnd.ms-powerpoint', ...self::OLE],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', ...self::OOXML_SNIFFS],
        'csv' => ['text/csv', 'text/plain', 'application/csv', 'text/x-csv', 'application/vnd.ms-excel'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ];

    /** The MIME type a document is stored and served with, by extension. */
    public const array DOCUMENT_MIME = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'csv' => 'text/csv',
        'zip' => 'application/zip',
    ];

    /** Short labels for the file-type mark on a document card. */
    public const array TYPE_LABELS = [
        'pdf' => 'PDF', 'doc' => 'Word', 'docx' => 'Word', 'xls' => 'Excel', 'xlsx' => 'Excel',
        'ppt' => 'PowerPoint', 'pptx' => 'PowerPoint', 'csv' => 'CSV', 'zip' => 'ZIP',
        'jpg' => 'JPEG', 'jpeg' => 'JPEG', 'png' => 'PNG', 'webp' => 'WebP', 'heic' => 'HEIC', 'heif' => 'HEIF',
    ];

    public static function kindFor(string $extension): ?ChatAttachmentKind
    {
        $extension = strtolower($extension);

        return match (true) {
            array_key_exists($extension, self::IMAGE_TYPES) => ChatAttachmentKind::Image,
            array_key_exists($extension, self::DOCUMENT_TYPES) => ChatAttachmentKind::Document,
            default => null,
        };
    }

    /**
     * MIME types the content of a file with this extension may sniff as.
     *
     * @return list<string>
     */
    public static function sniffsFor(string $extension): array
    {
        $extension = strtolower($extension);

        return self::IMAGE_TYPES[$extension] ?? self::DOCUMENT_TYPES[$extension] ?? [];
    }

    /**
     * Every accepted extension, for the file picker's accept attribute.
     *
     * @return list<string>
     */
    public static function extensions(): array
    {
        return [...array_keys(self::IMAGE_TYPES), ...array_keys(self::DOCUMENT_TYPES)];
    }

    public static function accept(): string
    {
        return implode(',', array_map(fn (string $extension): string => '.'.$extension, self::extensions()));
    }

    public static function typeLabel(string $extension): string
    {
        return self::TYPE_LABELS[strtolower($extension)] ?? strtoupper($extension);
    }
}
