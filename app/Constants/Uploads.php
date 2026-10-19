<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Upload limits and accepted formats for the admin screens. Images are capped
 * at 10 MB, documents (publication and teaching files) at 50 MB. Validation
 * rules are built from these values so the limits live in one place.
 */
final class Uploads
{
    public const int IMAGE_MAX_KILOBYTES = 10 * 1024;

    public const int DOCUMENT_MAX_KILOBYTES = 50 * 1024;

    /** @var list<string> */
    public const array IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** @var list<string> */
    public const array IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** @var list<string> */
    public const array DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip'];

    /** @var list<string> */
    public const array DOCUMENT_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-zip-compressed',
    ];

    /**
     * Validation rules for an image upload.
     *
     * @return list<string>
     */
    public static function imageRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'image',
            'mimes:'.implode(',', self::IMAGE_EXTENSIONS),
            'max:'.self::IMAGE_MAX_KILOBYTES,
        ];
    }

    /** @var list<string> */
    public const array VIDEO_EXTENSIONS = ['mp4', 'webm', 'm4v'];

    /**
     * Validation rules for an uploaded video file (same cap as documents).
     *
     * @return list<string>
     */
    public static function videoRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:'.implode(',', self::VIDEO_EXTENSIONS),
            'max:'.self::DOCUMENT_MAX_KILOBYTES,
        ];
    }

    /**
     * Validation rules for a document upload.
     *
     * @return list<string>
     */
    public static function documentRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:'.implode(',', self::DOCUMENT_EXTENSIONS),
            'max:'.self::DOCUMENT_MAX_KILOBYTES,
        ];
    }

    public static function imageMaxLabel(): string
    {
        return (self::IMAGE_MAX_KILOBYTES / 1024).' MB';
    }

    public static function documentMaxLabel(): string
    {
        return (self::DOCUMENT_MAX_KILOBYTES / 1024).' MB';
    }
}
