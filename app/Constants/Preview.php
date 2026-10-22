<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Signed preview links: an editor's draft shown on its real public page. The
 * link is the public address plus `preview=<type>.<id>` and a signature that
 * expires, so it can be checked (or sent to the family) before publishing.
 */
final class Preview
{
    /** Query parameter naming the record: "<ContentTypes key>.<id>". */
    public const string PARAM = 'preview';

    public const string SEPARATOR = '.';

    /** How long a preview link works. */
    public const int TTL_MINUTES = 120;

    /** Request attribute set while a preview is being rendered. */
    public const string ATTRIBUTE = 'preview.context';

    public const string BANNER_LABEL = 'Preview';

    public const string BANNER_TEXT = 'You are looking at a draft. Visitors cannot see it until it is published.';

    public const string ROBOTS = 'noindex, nofollow';

    public static function value(string $type, int $id): string
    {
        return $type.self::SEPARATOR.$id;
    }

    /**
     * @return array{type: string, id: int}|null
     */
    public static function parse(?string $value): ?array
    {
        if ($value === null || ! preg_match('/^([a-z_]+)'.preg_quote(self::SEPARATOR, '/').'(\d+)$/', $value, $matches)) {
            return null;
        }

        return ContentTypes::isValid($matches[1]) ? ['type' => $matches[1], 'id' => (int) $matches[2]] : null;
    }
}
