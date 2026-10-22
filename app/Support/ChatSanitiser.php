<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\ChatFormat;
use Dom\Element;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\Text;

/**
 * The allowlist that every rendered chat body passes through before it leaves
 * the server. The HTML is parsed with PHP's HTML5 parser and rebuilt node by
 * node: only the tags and attributes in App\Constants\ChatFormat survive,
 * links keep http(s) targets only and always carry the safe rel/target pair,
 * text and attribute values are re-escaped on the way out, and anything else
 * (scripts, styles, handlers, unknown tags) is dropped or unwrapped to text.
 */
final class ChatSanitiser
{
    /** Tags dropped together with their contents rather than unwrapped. */
    private const array DROPPED_WITH_CONTENT = ['script', 'style', 'template', 'iframe', 'object', 'embed', 'noscript', 'svg', 'math', 'head', 'title'];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = HTMLDocument::createFromString(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>',
            LIBXML_NOERROR,
            'UTF-8',
        );

        $body = $document->body;

        return $body === null ? '' : self::children($body);
    }

    private static function children(Node $parent): string
    {
        $out = '';

        foreach ($parent->childNodes as $child) {
            $out .= self::node($child);
        }

        return $out;
    }

    private static function node(Node $node): string
    {
        if ($node instanceof Text) {
            return e($node->data);
        }

        if (! $node instanceof Element) {
            return ''; // comments, processing instructions, CDATA
        }

        $tag = strtolower($node->localName);

        if (in_array($tag, self::DROPPED_WITH_CONTENT, true)) {
            return '';
        }

        $allowed = ChatFormat::allowedTags();

        if (! array_key_exists($tag, $allowed)) {
            return self::children($node);
        }

        $attributes = self::attributes($node, $tag, $allowed[$tag]);

        if ($attributes === null) {
            return self::children($node);
        }

        if ($tag === 'br') {
            return '<br>';
        }

        $open = $attributes === [] ? '<'.$tag.'>' : '<'.$tag.' '.implode(' ', $attributes).'>';

        return $open.self::children($node).'</'.$tag.'>';
    }

    /**
     * The attributes to print for an element, or null when the element must be unwrapped.
     *
     * @param  list<string>  $permitted
     * @return list<string>|null
     */
    private static function attributes(Element $element, string $tag, array $permitted): ?array
    {
        $values = [];

        foreach ($permitted as $name) {
            $value = $element->hasAttribute($name) ? (string) $element->getAttribute($name) : null;

            if ($name === 'class') {
                $value = self::classValue($value);
            } elseif ($name === ChatFormat::MATH_ATTRIBUTE) {
                $value = in_array($value, ChatFormat::mathValues(), true) ? $value : null;
            }

            if ($value !== null && $value !== '') {
                $values[$name] = $value;
            }
        }

        if ($tag === 'a') {
            if (! isset($values['href']) || ! self::safeUrl($values['href'])) {
                return null;
            }

            $values = ['href' => $values['href']] + ChatFormat::LINK_ATTRIBUTES;
        }

        if ($tag === 'span' && (! isset($values['class']) || $values['class'] !== ChatFormat::MATH_CLASS || ! isset($values[ChatFormat::MATH_ATTRIBUTE]))) {
            return null;
        }

        if ($tag === 'code') {
            $values = ['class' => ChatFormat::CODE_CLASS];
        }

        $printed = [];

        foreach ($values as $name => $value) {
            $printed[] = $name.'="'.e($value).'"';
        }

        return $printed;
    }

    private static function classValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $kept = array_values(array_intersect(preg_split('/\s+/', trim($value)) ?: [], ChatFormat::allowedClasses()));

        return $kept === [] ? null : $kept[0];
    }

    /**
     * Only absolute http(s) URLs may be linked; anything else becomes plain text.
     */
    public static function safeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || preg_match('/[\x00-\x20]/', $url) === 1) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ChatFormat::LINK_SCHEMES, true) && parse_url($url, PHP_URL_HOST) !== null;
    }
}
