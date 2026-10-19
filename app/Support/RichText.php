<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Reduces editor-supplied HTML (the biography, commentary bodies) to the small
 * set of elements the public templates style, with no attributes other than a
 * safe href. Everything else is removed before the HTML is echoed unescaped.
 */
final class RichText
{
    /**
     * @var list<string>
     */
    public const array ALLOWED_TAGS = [
        'p', 'h2', 'h3', 'h4', 'blockquote', 'em', 'strong', 'i', 'b', 'a', 'ul', 'ol', 'li', 'br', 'cite',
    ];

    public static function sanitise(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $clean = strip_tags($html, self::ALLOWED_TAGS);

        // Drop every attribute, keeping only http(s) and mailto hrefs on links.
        $clean = (string) preg_replace_callback(
            '/<([a-z0-9]+)(\s[^>]*)?>/i',
            static function (array $match): string {
                $tag = strtolower($match[1]);
                $attributes = $match[2] ?? '';

                if ($tag === 'a' && preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/is', $attributes, $href)) {
                    $url = trim(html_entity_decode($href[2], ENT_QUOTES | ENT_HTML5));

                    if (preg_match('#^(https?://|mailto:)#i', $url)) {
                        return sprintf('<a href="%s" rel="noopener">', e($url));
                    }
                }

                return '<'.$tag.'>';
            },
            $clean,
        );

        return trim($clean);
    }

    /**
     * Whether the sanitised HTML contains a block quote (the pull quote).
     */
    public static function hasBlockquote(string $html): bool
    {
        return stripos($html, '<blockquote>') !== false;
    }
}
