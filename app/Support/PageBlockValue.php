<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\PageBlockType;
use App\Constants\PageLinks;

/**
 * Brings any value for a page block into its type's shape, applying the
 * guardrails: rich text is sanitised, short text is trimmed and capped, a
 * missing value (or an emptied required one) becomes the default, links keep
 * only destinations from the list (or a real web address), and lists keep
 * their fixed items and drop broken ones. Used both before a value is stored
 * and when a stored value is rendered, so nothing ever renders empty or unsafe.
 */
final class PageBlockValue
{
    public static function normalise(PageBlockDefinition $definition, mixed $value): mixed
    {
        return match ($definition->type) {
            PageBlockType::ShortText => self::shortText($definition, $value),
            PageBlockType::LongText => self::longText($definition, $value),
            PageBlockType::Link => self::link($definition, $value),
            PageBlockType::List => self::items($definition, $value),
            PageBlockType::Image => self::image($value),
            PageBlockType::Seo => self::seo($definition, $value),
        };
    }

    /**
     * Whether the value is the block's default (so the editor can mark changed blocks).
     */
    public static function isDefault(PageBlockDefinition $definition, mixed $value): bool
    {
        return self::normalise($definition, $value) == self::normalise($definition, null);
    }

    private static function shortText(PageBlockDefinition $definition, mixed $value): string
    {
        if (! is_string($value)) {
            return (string) $definition->default;
        }

        $text = trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value));

        if ($text === '') {
            return $definition->required ? (string) $definition->default : '';
        }

        return $definition->max === null ? $text : mb_substr($text, 0, $definition->max);
    }

    private static function longText(PageBlockDefinition $definition, mixed $value): string
    {
        if (! is_string($value)) {
            return (string) $definition->default;
        }

        $html = $definition->inline ? RichText::inline($value) : RichText::sanitise($value);

        if (trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5)) === '') {
            return $definition->required ? (string) $definition->default : '';
        }

        return $html;
    }

    /**
     * @return array{label: string, route: string|null, url: string|null}
     */
    private static function link(PageBlockDefinition $definition, mixed $value): array
    {
        $default = (array) $definition->default;
        $value = is_array($value) ? $value : [];

        $label = self::text($value['label'] ?? null, $definition->max) ?? $default['label'];
        $route = is_string($value['route'] ?? null) && PageLinks::isValid($value['route']) ? $value['route'] : null;
        $url = self::url($value['url'] ?? null);

        if ($route === null && $url === null) {
            $route = $default['route'];
        }

        return ['label' => $label, 'route' => $url === null ? $route : null, 'url' => $url];
    }

    /**
     * @return list<array<string, string>>
     */
    private static function items(PageBlockDefinition $definition, mixed $value): array
    {
        $defaults = (array) $definition->default;

        if (! is_array($value) || $value === []) {
            return array_values($defaults);
        }

        return $definition->fixedItems
            ? self::fixedItems($definition, $value, $defaults)
            : self::freeItems($definition, $value, $defaults);
    }

    /**
     * Lists whose items are set by the design (chapters, sections): every item
     * stays, in the editor's order when the list is sortable, and an emptied
     * field takes the item's default.
     *
     * @param  array<mixed>  $value
     * @param  array<int, array<string, string>>  $defaults
     * @return list<array<string, string>>
     */
    private static function fixedItems(PageBlockDefinition $definition, array $value, array $defaults): array
    {
        $byKey = [];

        foreach ($defaults as $item) {
            $byKey[$item['key']] = $item;
        }

        $items = [];

        foreach ($value as $item) {
            $key = is_array($item) ? ($item['key'] ?? null) : null;

            if (! is_string($key) || ! isset($byKey[$key]) || isset($items[$key])) {
                continue;
            }

            $clean = ['key' => $key];

            foreach ($definition->editableFields() as $field => $spec) {
                $clean[$field] = self::text($item[$field] ?? null, $spec['max']) ?? $byKey[$key][$field];
            }

            $items[$key] = $clean;
        }

        foreach ($byKey as $key => $item) {
            $items[$key] ??= $item;
        }

        if (! $definition->sortable) {
            $items = array_replace(array_intersect_key($byKey, $items), $items);
        }

        return array_values($items);
    }

    /**
     * Lists editors add to (conduct rules, footer links): blank items and links
     * to destinations not in the list are dropped; an emptied list returns to
     * the default.
     *
     * @param  array<mixed>  $value
     * @param  array<int, array<string, string>>  $defaults
     * @return list<array<string, string>>
     */
    private static function freeItems(PageBlockDefinition $definition, array $value, array $defaults): array
    {
        $items = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $clean = [];

            foreach ($definition->editableFields() as $field => $spec) {
                $text = self::text($item[$field] ?? null, $spec['max']);

                if ($text === null || ($spec['kind'] === PageBlockDefinition::FIELD_ROUTE && ! PageLinks::isValid($text))) {
                    continue 2;
                }

                $clean[$field] = $text;
            }

            $items[] = $clean;
        }

        if ($definition->maxItems !== null) {
            $items = array_slice($items, 0, $definition->maxItems);
        }

        return $items === [] ? array_values($defaults) : $items;
    }

    /**
     * @return array{media_id: int|null, alt_text: string|null, caption: string|null, place: string|null, year: int|null, focus_x: int, focus_y: int}
     */
    private static function image(mixed $value): array
    {
        $value = is_array($value) ? $value : [];
        $year = filter_var($value['year'] ?? null, FILTER_VALIDATE_INT);

        return [
            'media_id' => self::id($value['media_id'] ?? null),
            'alt_text' => self::text($value['alt_text'] ?? null, PageBlockDefinition::IMAGE_TEXT_MAX),
            'caption' => self::text($value['caption'] ?? null, PageBlockDefinition::IMAGE_TEXT_MAX),
            'place' => self::text($value['place'] ?? null, PageBlockDefinition::IMAGE_TEXT_MAX),
            'year' => $year === false ? null : $year,
            'focus_x' => self::percent($value['focus_x'] ?? null),
            'focus_y' => self::percent($value['focus_y'] ?? null),
        ];
    }

    /**
     * @return array{title: string|null, description: string|null, media_id: int|null, alt_text: string|null}
     */
    private static function seo(PageBlockDefinition $definition, mixed $value): array
    {
        $default = (array) $definition->default;

        if (! is_array($value)) {
            return $default;
        }

        return [
            'title' => self::text($value['title'] ?? null, PageBlockDefinition::SEO_TITLE_MAX),
            'description' => self::text($value['description'] ?? null, PageBlockDefinition::SEO_DESCRIPTION_MAX),
            'media_id' => self::id($value['media_id'] ?? null),
            'alt_text' => self::text($value['alt_text'] ?? null, PageBlockDefinition::IMAGE_TEXT_MAX),
        ];
    }

    /**
     * Trimmed plain text cut to the limit, or null when blank.
     */
    private static function text(mixed $value, ?int $max): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $text = trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $value));

        if ($text === '') {
            return null;
        }

        return $max === null ? $text : mb_substr($text, 0, $max);
    }

    private static function url(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '' || mb_strlen($value) > PageLinks::URL_MAX_LENGTH) {
            return null;
        }

        $url = trim($value);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return filter_var($url, FILTER_VALIDATE_URL) !== false && in_array($scheme, PageLinks::URL_SCHEMES, true) ? $url : null;
    }

    private static function id(mixed $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private static function percent(mixed $value): int
    {
        $percent = filter_var($value, FILTER_VALIDATE_INT);

        return $percent === false ? PageBlockDefinition::FOCUS_CENTRE : max(0, min(100, $percent));
    }
}
