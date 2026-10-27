<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The light formatting a chat message may carry and the HTML allowlist the
 * server enforces when it renders one. Bodies are stored as the member wrote
 * them (plain text with markers); ChatText turns the markers into HTML and
 * ChatSanitiser lets nothing else through. Maths is kept as its TeX source in
 * a span the browser typesets with KaTeX, which only chat pages load.
 */
final class ChatFormat
{
    /** Inline markers. */
    public const string BOLD = '**';

    public const string ITALIC = '*';

    public const string ITALIC_ALT = '_';

    public const string CODE = '`';

    /** Maths delimiters: $$…$$ for a displayed formula, $…$ inline. */
    public const string MATH_DISPLAY = '$$';

    public const string MATH_INLINE = '$';

    /** Lines starting with one of these (then a space) form a bulleted list. */
    public const array BULLETS = ['-', '*', '•'];

    /** Attribute and class on the span that carries TeX for the browser. */
    public const string MATH_ATTRIBUTE = 'data-math';

    public const string MATH_CLASS = 'chat-math';

    public const string MATH_INLINE_VALUE = 'inline';

    public const string MATH_DISPLAY_VALUE = 'display';

    /** Class on an inline code span. */
    public const string CODE_CLASS = 'chat-code';

    /** Attributes added to every link in a message. */
    public const array LINK_ATTRIBUTES = [
        'rel' => 'nofollow noopener noreferrer',
        'target' => '_blank',
    ];

    /** URL schemes a link may use; anything else is shown as text. */
    public const array LINK_SCHEMES = ['http', 'https'];

    /**
     * Tags the rendered body may contain, each with the attributes it may carry.
     *
     * @return array<string, list<string>>
     */
    public static function allowedTags(): array
    {
        return [
            'strong' => [],
            'em' => [],
            'code' => ['class'],
            'a' => ['href', 'rel', 'target'],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'br' => [],
            'span' => ['class', self::MATH_ATTRIBUTE],
        ];
    }

    /**
     * Class values the allowlist accepts on the tags above.
     *
     * @return list<string>
     */
    public static function allowedClasses(): array
    {
        return [self::CODE_CLASS, self::MATH_CLASS];
    }

    /**
     * @return list<string>
     */
    public static function mathValues(): array
    {
        return [self::MATH_INLINE_VALUE, self::MATH_DISPLAY_VALUE];
    }

    /**
     * The formatting hints shown to members, in order (what each means in the current language).
     *
     * @return list<array{markup: string, means: string}>
     */
    public static function hints(): array
    {
        return [
            ['markup' => '**bold**', 'means' => __('bold')],
            ['markup' => '*italic*', 'means' => __('italic')],
            ['markup' => '`code`', 'means' => __('inline code')],
            ['markup' => '- item', 'means' => __('a list')],
            ['markup' => '[text](https://…)', 'means' => __('a link')],
            ['markup' => '$E = mc^2$', 'means' => __('maths (KaTeX)')],
        ];
    }
}
