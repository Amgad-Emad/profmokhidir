<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Publication status shared by every public content type.
 * Models expose it through a `status` column and a `published()` scope.
 */
final class ContentStatus
{
    public const string DRAFT = 'draft';

    public const string PUBLISHED = 'published';

    public const string DEFAULT = self::DRAFT;

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::DRAFT, self::PUBLISHED];
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::all(), true);
    }
}
