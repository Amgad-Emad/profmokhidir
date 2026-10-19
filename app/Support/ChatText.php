<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Renders a chat message body as safe HTML: everything is escaped, then bare
 * URLs become links that never pass referrer or window access to the target.
 * Line breaks are preserved by the caller with white-space: pre-wrap.
 */
final class ChatText
{
    /**
     * Matches http(s) URLs in already-escaped text ("&amp;" is part of a URL),
     * stopping before whitespace, quotes and angle brackets. Trailing
     * punctuation is handled separately so "see https://x.org." links x.org.
     */
    private const string URL_PATTERN = '~\bhttps?://[^\s<>"\']+~iu';

    private const string TRAILING_PUNCTUATION = '.,;:!?)]}\'"';

    /** Link attributes applied to every auto-linked URL. */
    public const string LINK_ATTRIBUTES = 'rel="nofollow noopener noreferrer" target="_blank"';

    public static function toHtml(string $body): string
    {
        $escaped = e(trim($body));

        return (string) preg_replace_callback(self::URL_PATTERN, function (array $match): string {
            [$url, $trailing] = self::splitTrailingPunctuation($match[0]);

            return sprintf('<a href="%s" %s>%s</a>%s', $url, self::LINK_ATTRIBUTES, $url, $trailing);
        }, $escaped);
    }

    /**
     * Plain text normalised the way the server stores it: trimmed, CRLF folded to LF.
     */
    public static function normalise(string $body): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $body));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function splitTrailingPunctuation(string $url): array
    {
        $trailing = '';

        while ($url !== '' && str_contains(self::TRAILING_PUNCTUATION, substr($url, -1))) {
            // Keep a closing bracket when the URL opened one (Wikipedia-style links).
            if (substr($url, -1) === ')' && substr_count($url, '(') > substr_count($url, ')') - 1) {
                break;
            }

            $trailing = substr($url, -1).$trailing;
            $url = substr($url, 0, -1);
        }

        // "&amp;" cut in half by the loop above would leave "&amp" behind; put the semicolon back.
        if (str_ends_with($url, '&amp') && str_starts_with($trailing, ';')) {
            $url .= ';';
            $trailing = substr($trailing, 1);
        }

        return [$url, $trailing];
    }
}
