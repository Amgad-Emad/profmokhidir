<?php

declare(strict_types=1);

namespace App\Constants;

use App\Models\Page;
use Illuminate\Database\Eloquent\Model;

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

    /** Preview type of an editable page (Admin › Pages): its drafts shown on the real page. */
    public const string PAGE = 'page';

    /** How long a preview link works. */
    public const int TTL_MINUTES = 120;

    /**
     * On a page previewed beside its editor, each region names the editor section that changes
     * it (the form section's id without "section-"), so the preview can point at it.
     */
    public const string EDIT_SECTION_ATTRIBUTE = 'data-edit-section';

    /**
     * On a page previewed beside its editor, an element showing one field's text names that field
     * ("page:<block key>" for the page's own words, "profile:<column>" for the profile), so the
     * preview can show the words as they are typed, before the draft is saved.
     */
    public const string EDIT_FIELD_ATTRIBUTE = 'data-edit-field';

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

        return ContentTypes::isValid($matches[1]) || $matches[1] === self::PAGE ? ['type' => $matches[1], 'id' => (int) $matches[2]] : null;
    }

    /**
     * The model a preview type names: a content type's, or the Page.
     *
     * @return class-string<Model>
     */
    public static function modelFor(string $type): string
    {
        return $type === self::PAGE ? Page::class : ContentTypes::get($type)['model'];
    }
}
