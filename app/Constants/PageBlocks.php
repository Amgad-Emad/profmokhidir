<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\PageDefinitions\CommunityBlocks;
use App\Constants\PageDefinitions\HomeBlocks;
use App\Constants\PageDefinitions\SectionBlocks;
use App\Constants\PageDefinitions\SiteBlocks;
use App\Constants\PageDefinitions\TributeBlocks;
use App\Support\PageBlockDefinition;
use InvalidArgumentException;

/**
 * Every editable block of every page (App\Constants\Pages), in the order the
 * blocks appear on the site. The definitions live per page in
 * App\Constants\PageDefinitions; this class looks them up. A block that is not
 * declared here cannot be stored, so editors can only fill the slots the
 * design has.
 */
final class PageBlocks
{
    /**
     * @return array<string, PageBlockDefinition> block key => definition, in site order
     */
    public static function for(string $page): array
    {
        $definitions = match ($page) {
            Pages::HOME => HomeBlocks::definitions(),
            Pages::COMMUNITY => CommunityBlocks::definitions(),
            Pages::TRIBUTES => TributeBlocks::definitions(),
            Pages::NAVIGATION, Pages::PRIVACY, Pages::ERRORS => SiteBlocks::definitions($page),
            default => Pages::isValid($page) ? SectionBlocks::definitions($page) : throw new InvalidArgumentException(sprintf('Unknown page "%s".', $page)),
        };

        $keyed = [];

        foreach ($definitions as $definition) {
            $keyed[$definition->key] = $definition;
        }

        return $keyed;
    }

    public static function get(string $page, string $key): PageBlockDefinition
    {
        return self::for($page)[$key] ?? throw new InvalidArgumentException(sprintf('Page "%s" has no block "%s".', $page, $key));
    }

    public static function has(string $page, string $key): bool
    {
        return Pages::isValid($page) && array_key_exists($key, self::for($page));
    }

    /**
     * The admin sections of a page, in order, each with its block keys.
     *
     * @return array<string, list<string>> section heading => block keys
     */
    public static function sections(string $page): array
    {
        $sections = [];

        foreach (self::for($page) as $key => $definition) {
            $sections[$definition->section][] = $key;
        }

        return $sections;
    }

    /**
     * Every block's default value, keyed by block key.
     *
     * @return array<string, mixed>
     */
    public static function defaults(string $page): array
    {
        return array_map(fn (PageBlockDefinition $definition): mixed => $definition->default, self::for($page));
    }
}
