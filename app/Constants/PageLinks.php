<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Where an editable button or footer link may point: the public pages,
 * chosen from a list so a link can never break. A link block may instead
 * hold a full web address (checked as a URL on save).
 */
final class PageLinks
{
    /** Schemes a typed web address may use. */
    public const array URL_SCHEMES = ['https', 'http'];

    /** Longest web address a link accepts. */
    public const int URL_MAX_LENGTH = 255;

    /**
     * Route name => the page's name in the destination list, in site order.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = ['home' => 'Home'];

        foreach (Sections::definitions() as $section) {
            $options[$section['route']] = $section['title'];
        }

        return $options + [
            'tributes.create' => 'Leave a tribute (the tribute form)',
            'community.chat' => 'Community chat (members only)',
            Search::ROUTE => 'Search',
            Analytics::PRIVACY_ROUTE => 'Privacy (only while visitor analytics is on)',
        ];
    }

    /**
     * @return list<string>
     */
    public static function routes(): array
    {
        return array_keys(self::options());
    }

    public static function isValid(string $route): bool
    {
        return array_key_exists($route, self::options());
    }
}
