<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

/**
 * What an editable page slot (a page block) holds. Each type has one value
 * shape, one admin field and one way of rendering; editors change words and
 * images, never markup or styles.
 *
 * - ShortText: a plain line (string).
 * - LongText: sanitised rich text (string of HTML; em, strong and links, or
 *   headings and lists where the block allows them).
 * - Image: a media item of the block with its alt text, caption, place, year
 *   and crop focus point ({media_id, alt_text, caption, place, year, focus_x, focus_y}).
 * - Link: a label and a destination chosen from PageLinks, or a web address ({label, route, url}).
 * - List: ordered items, each a small set of plain fields (list<array<string, string>>).
 * - Seo: the search title, description and share image ({title, description, media_id, alt_text}).
 */
enum PageBlockType: string
{
    use HasValues;

    case ShortText = 'short_text';
    case LongText = 'long_text';
    case Image = 'image';
    case Link = 'link';
    case List = 'list';
    case Seo = 'seo';

    public function label(): string
    {
        return match ($this) {
            self::ShortText => 'Short text',
            self::LongText => 'Long text',
            self::Image => 'Image',
            self::Link => 'Link',
            self::List => 'List',
            self::Seo => 'Search & sharing',
        };
    }

    /**
     * Whether the block's value is a set of fields rather than one string.
     */
    public function isStructured(): bool
    {
        return in_array($this, [self::Image, self::Link, self::List, self::Seo], true);
    }

    /**
     * Whether the block holds an uploaded image (the image itself, or the share image).
     */
    public function hasImage(): bool
    {
        return $this === self::Image || $this === self::Seo;
    }
}
