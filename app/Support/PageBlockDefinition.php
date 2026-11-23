<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\Locales;
use App\Constants\PageBlockType;

/**
 * One editable slot of a public page, as declared in App\Constants\PageBlocks:
 * its key, type, the plain-words label editors see, the admin section it sits
 * in (in the order the blocks appear on the site), the default (the copy that
 * was hardcoded before pages became editable, and the fallback whenever the
 * block is missing or emptied) and the guardrails that protect the layout.
 */
final readonly class PageBlockDefinition
{
    /** List item field kinds. */
    public const string FIELD_TEXT = 'text';

    /** A destination chosen from PageLinks::options(). */
    public const string FIELD_ROUTE = 'route';

    /** Set by the definition and shown, never edited (for example a chapter's key). */
    public const string FIELD_FIXED = 'fixed';

    /** Text that is the same in every language (years, figures): no Arabic version is asked for. */
    public const string FIELD_PLAIN = 'plain';

    /** An uploaded image (the item's media_id), shown with the item's alt text, caption, place and year. */
    public const string FIELD_IMAGE = 'image';

    /** An on/off switch (stored as true or false), e.g. "Hide this chapter". */
    public const string FIELD_TOGGLE = 'toggle';

    /** The switch that keeps a list item in the editor but off the page. */
    public const string HIDDEN = 'hidden';

    /** Search titles longer than this are cut off in results. */
    public const int SEO_TITLE_MAX = 60;

    /** Matches Seo::DESCRIPTION_MAX_LENGTH, where descriptions are cut. */
    public const int SEO_DESCRIPTION_MAX = 160;

    /** Alt text, caption and other image detail fields. */
    public const int IMAGE_TEXT_MAX = 255;

    /** Default crop focus: the centre of the image. */
    public const int FOCUS_CENTRE = 50;

    /**
     * @param  array<string, string>  $tokens  placeholder => what it is replaced with, e.g. ':year' => 'the current year'
     * @param  array<string, array{label: string, kind: string, max: int|null}>  $fields  list item fields
     */
    public function __construct(
        public string $key,
        public PageBlockType $type,
        public string $label,
        public string $section,
        public mixed $default,
        public ?int $max = null,
        public bool $required = true,
        public ?string $hint = null,
        public bool $inline = true,
        public array $tokens = [],
        public array $fields = [],
        public bool $fixedItems = false,
        public bool $sortable = false,
        public ?int $maxItems = null,
        public ?string $itemLabel = null,
    ) {}

    /**
     * @param  array<string, string>  $tokens
     */
    public static function shortText(string $key, string $label, string $section, string $default, int $max, bool $required = true, ?string $hint = null, array $tokens = []): self
    {
        return new self($key, PageBlockType::ShortText, $label, $section, $default, $max, $required, $hint, tokens: $tokens);
    }

    /**
     * Sanitised rich text. Inline blocks sit inside one paragraph of the design
     * (emphasis and links only); block text may also carry headings and lists.
     *
     * @param  array<string, string>  $tokens
     */
    public static function longText(string $key, string $label, string $section, string $default, int $max, bool $required = true, ?string $hint = null, bool $inline = true, array $tokens = []): self
    {
        return new self($key, PageBlockType::LongText, $label, $section, $default, $max, $required, $hint, $inline, $tokens);
    }

    public static function image(string $key, string $label, string $section, ?string $hint = null): self
    {
        return new self($key, PageBlockType::Image, $label, $section, self::emptyImage(), required: false, hint: $hint);
    }

    public static function link(string $key, string $label, string $section, string $defaultLabel, string $defaultRoute, int $max, ?string $hint = null): self
    {
        return new self($key, PageBlockType::Link, $label, $section, ['label' => $defaultLabel, 'route' => $defaultRoute, 'url' => null], $max, hint: $hint);
    }

    /**
     * A list of items. `fixedItems`: editors change the items but cannot add or
     * remove any; `sortable`: they may change the order.
     *
     * @param  list<array<string, string>>  $default
     * @param  array<string, array{label: string, kind: string, max: int|null}>  $fields
     */
    public static function list(string $key, string $label, string $section, array $default, array $fields, string $itemLabel, bool $fixedItems = false, bool $sortable = true, ?int $maxItems = null, ?string $hint = null): self
    {
        return new self($key, PageBlockType::List, $label, $section, $default, hint: $hint, fields: $fields, fixedItems: $fixedItems, sortable: $sortable, maxItems: $maxItems, itemLabel: $itemLabel);
    }

    /**
     * Search title and description (empty means automatic: the page heading and
     * its intro) and the share image (empty means the static share card).
     * `text: false` leaves only the share image (the site-wide default card).
     */
    public static function seo(string $section, ?string $title = null, ?string $description = null, ?string $hint = null, bool $text = true): self
    {
        return new self('seo', PageBlockType::Seo, 'Search & sharing', $section, [
            'title' => $title,
            'description' => $description,
            'media_id' => null,
            'alt_text' => null,
        ], required: false, hint: $hint, fields: $text ? [
            'title' => self::field('Search title', self::SEO_TITLE_MAX),
            'description' => self::field('Search description', self::SEO_DESCRIPTION_MAX),
        ] : []);
    }

    /**
     * A list item field. An optional field may be left empty; an item missing a
     * required field is dropped when saved.
     *
     * `group` gathers fields under one heading inside an item (a chapter's "Milestone 1", "Photo").
     *
     * @return array{label: string, kind: string, max: int|null, required: bool, group: ?string}
     */
    public static function field(string $label, int $max, string $kind = self::FIELD_TEXT, bool $required = true, ?string $group = null): array
    {
        return ['label' => $label, 'kind' => $kind, 'max' => $max, 'required' => $required, 'group' => $group];
    }

    /**
     * An item's image, always optional. Store it under the item's media_id.
     *
     * @return array{label: string, kind: string, max: int|null, required: bool}
     */
    public static function imageField(string $label, ?string $group = null): array
    {
        return ['label' => $label, 'kind' => self::FIELD_IMAGE, 'max' => null, 'required' => false, 'group' => $group];
    }

    /**
     * The "Hide this ..." switch of a list item: hidden items stay in the editor and leave the page.
     *
     * @return array{label: string, kind: string, max: int|null, required: bool, group: ?string}
     */
    public static function hiddenField(string $label): array
    {
        return ['label' => $label, 'kind' => self::FIELD_TOGGLE, 'max' => null, 'required' => false, 'group' => null];
    }

    /**
     * The items a page shows: hidden ones left out.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public static function visibleItems(array $items): array
    {
        return array_values(array_filter($items, fn (mixed $item): bool => is_array($item) && ! ($item[self::HIDDEN] ?? false)));
    }

    /**
     * The fields of one group in the order they lay out best: switches, then short
     * fields (side by side), then long text and images (full width).
     *
     * @param  array<string, array{kind: string, max: int|null}>  $fields
     * @return array<string, array{kind: string, max: int|null}>
     */
    public static function laidOut(array $fields): array
    {
        $rank = fn (array $spec): int => match (true) {
            $spec['kind'] === self::FIELD_TOGGLE => 0,
            $spec['kind'] === self::FIELD_IMAGE || self::isLong($spec) => 2,
            default => 1,
        };

        $order = array_keys($fields);
        uksort($fields, fn (string $a, string $b): int => [$rank($fields[$a]), array_search($a, $order, true)] <=> [$rank($fields[$b]), array_search($b, $order, true)]);

        return $fields;
    }

    /**
     * Whether a field is a short one-line box (half width when it has a short neighbour).
     *
     * @param  array{kind: string, max: int|null}  $spec
     */
    public static function isShort(array $spec): bool
    {
        return in_array($spec['kind'], [self::FIELD_TEXT, self::FIELD_PLAIN, self::FIELD_ROUTE], true) && ! self::isLong($spec);
    }

    /**
     * Fields gathered by their group, in order: [group label or '' => [field => spec]].
     *
     * @param  array<string, array{label: string, kind: string, max: int|null, group?: ?string}>  $fields
     * @return array<string, array<string, array{label: string, kind: string, max: int|null}>>
     */
    public static function grouped(array $fields): array
    {
        $groups = [];

        foreach ($fields as $field => $spec) {
            $groups[(string) ($spec['group'] ?? '')][$field] = $spec;
        }

        return $groups;
    }

    /** List fields allowed this many characters or more are edited in a growing text area, full width. */
    public const int LONG_FIELD = 120;

    /**
     * Whether a list field holds enough words to need a text area rather than one line.
     *
     * @param  array{kind: string, max: int|null}  $spec
     */
    public static function isLong(array $spec): bool
    {
        return in_array($spec['kind'], [self::FIELD_TEXT, self::FIELD_PLAIN], true) && (int) $spec['max'] >= self::LONG_FIELD;
    }

    /**
     * Whether a field is edited on the item's heading row (shown even while the item is folded).
     *
     * @param  array{inline?: bool}  $spec
     */
    public static function isInline(array $spec): bool
    {
        return (bool) ($spec['inline'] ?? false);
    }

    /**
     * A field edited on the item's heading row, e.g. a chapter's numeral and years.
     *
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    public static function inline(array $spec): array
    {
        return ['inline' => true] + $spec;
    }

    /**
     * Whether a list item may leave this field empty.
     *
     * @param  array{required?: bool}  $spec
     */
    public static function isRequired(array $spec): bool
    {
        return $spec['required'] ?? true;
    }

    /**
     * @return array{label: string, kind: string, max: int|null}
     */
    public static function fixedField(string $label): array
    {
        return ['label' => $label, 'kind' => self::FIELD_FIXED, 'max' => null];
    }

    /**
     * @return array{media_id: null, alt_text: null, caption: null, place: null, year: null, focus_x: int, focus_y: int}
     */
    public static function emptyImage(): array
    {
        return ['media_id' => null, 'alt_text' => null, 'caption' => null, 'place' => null, 'year' => null, 'focus_x' => self::FOCUS_CENTRE, 'focus_y' => self::FOCUS_CENTRE];
    }

    /** Value keys that are never words: destinations, ids, positions and years. */
    private const array UNTRANSLATED_KEYS = ['key', 'route', 'url', 'media_id', 'focus_x', 'focus_y', 'year'];

    /**
     * The same block with its built-in copy in the current language (each
     * default string through the JSON translations; English as it is).
     */
    public function localized(): self
    {
        if (app()->getLocale() === Locales::DEFAULT) {
            return $this;
        }

        return new self(
            $this->key, $this->type, $this->label, $this->section, $this->translate($this->default), $this->max, $this->required,
            $this->hint, $this->inline, $this->tokens, $this->fields, $this->fixedItems, $this->sortable, $this->maxItems, $this->itemLabel,
        );
    }

    /**
     * Every word of a value in the current language where a translation of
     * it exists (the built-in copy has one; text an editor changed in English
     * has none and stays as written). Destinations, ids and years are left alone.
     */
    public function translate(mixed $value): mixed
    {
        if (is_string($value)) {
            return $value === '' ? $value : __($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        $skip = [...self::UNTRANSLATED_KEYS, ...array_keys(array_filter($this->fields, fn (array $field): bool => $field['kind'] === self::FIELD_ROUTE))];
        $translated = [];

        foreach ($value as $key => $item) {
            $translated[$key] = is_string($key) && in_array($key, $skip, true) ? $item : $this->translate($item);
        }

        return $translated;
    }

    /**
     * Fields of a list item that editors may change.
     *
     * @return array<string, array{label: string, kind: string, max: int|null}>
     */
    public function editableFields(): array
    {
        return array_filter($this->fields, fn (array $field): bool => $field['kind'] !== self::FIELD_FIXED);
    }

    /**
     * A blank list item (fixed fields empty), for "Add" on lists that allow it.
     *
     * @return array<string, string>
     */
    public function blankItem(): array
    {
        return array_map(fn (array $field): ?string => $field['kind'] === self::FIELD_IMAGE ? null : '', $this->fields);
    }

    /**
     * The list's image fields (an item holds at most one, under media_id).
     *
     * @return array<string, array{label: string, kind: string, max: int|null}>
     */
    public function imageFields(): array
    {
        return array_filter($this->fields, fn (array $field): bool => $field['kind'] === self::FIELD_IMAGE);
    }
}
