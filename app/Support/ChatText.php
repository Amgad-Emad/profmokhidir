<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\ChatFormat;
use App\Constants\ChatSettings;
use Illuminate\Support\Str;

/**
 * Renders a chat message body as safe HTML with the light formatting of
 * App\Constants\ChatFormat: **bold**, *italic*, `code`, "- " lists, [text](url)
 * links, bare URLs and $maths$. Everything is escaped first; code and maths are
 * lifted out before any marker is read so their contents stay literal; links
 * never pass referrer or window access to the target; and the result passes
 * through ChatSanitiser's allowlist before it leaves. Line breaks are kept by
 * the caller with white-space: pre-wrap.
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

    /** Marks a lifted-out span (code, maths, link) while the markers are read. */
    private const string TOKEN = "\x1A";

    private const string MARKDOWN_LINK = '~\[([^\[\]\n]{1,200})\]\((https?://[^\s<>()"\']+)\)~iu';

    private const string DISPLAY_MATH = '~\$\$(?=\S)(.+?)(?<=\S)\$\$~su';

    private const string INLINE_MATH = '~(?<![\\\\$\w])\$(?=[^\s$])((?:\\\\.|[^\n$\\\\])+?)(?<=[^\s$\\\\])\$(?![\w$])~u';

    private const string CODE_SPAN = '~`([^`\n]+)`~u';

    private const string BULLET_LINE = '~^\s{0,3}[-*•]\s+(\S.*)$~u';

    private const string NUMBERED_LINE = '~^\s{0,3}\d{1,3}[.)]\s+(\S.*)$~u';

    public static function toHtml(string $body): string
    {
        $formatter = new self;

        return ChatSanitiser::clean($formatter->format(self::normalise($body)));
    }

    /**
     * Plain text normalised the way the server stores it: trimmed, CRLF folded to LF.
     */
    public static function normalise(string $body): string
    {
        return trim(str_replace(["\r\n", "\r", self::TOKEN], ["\n", "\n", ''], $body));
    }

    /**
     * A one-line plain excerpt of a body (for reply quotes and pins): markers
     * removed, links reduced to their text, whitespace collapsed.
     */
    public static function excerpt(string $body, int $length = ChatSettings::REPLY_EXCERPT_LENGTH): string
    {
        $plain = preg_replace(self::MARKDOWN_LINK, '$1', self::normalise($body)) ?? '';
        $plain = preg_replace(
            ['~\*\*(?=\S)(.+?)(?<=\S)\*\*~su', '~(?<![\w*])\*(?=[^\s*])(.+?)(?<=[^\s*])\*(?![\w*])~u', '~(?<![\w])_(?=[^\s_])(.+?)(?<=[^\s_])_(?![\w])~u', '~`([^`\n]+)`~u', '~^\s{0,3}(?:[-*•]|\d{1,3}[.)])\s+~mu'],
            ['$1', '$1', '$1', '$1', ''],
            $plain,
        ) ?? '';
        $plain = preg_replace('~\s+~u', ' ', $plain) ?? '';

        return Str::limit(trim($plain), $length, '…');
    }

    /** @var list<string> */
    private array $spans = [];

    private function format(string $body): string
    {
        if ($body === '') {
            return '';
        }

        $body = $this->lift($body, self::DISPLAY_MATH, fn (array $m): string => $this->math($m[1], ChatFormat::MATH_DISPLAY_VALUE));
        $body = $this->lift($body, self::INLINE_MATH, fn (array $m): string => $this->math($m[1], ChatFormat::MATH_INLINE_VALUE));
        $body = $this->lift($body, self::CODE_SPAN, fn (array $m): string => '<code class="'.ChatFormat::CODE_CLASS.'">'.e($m[1]).'</code>');
        $body = str_replace('\$', '$', $body);

        $escaped = e($body);
        $escaped = $this->lift($escaped, self::MARKDOWN_LINK, fn (array $m): string => $this->link(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'), $m[1]));
        $escaped = $this->lift($escaped, self::URL_PATTERN, function (array $m): string {
            [$url, $trailing] = self::splitTrailingPunctuation($m[0]);

            return $this->link(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $url).$trailing;
        });

        return $this->restore($this->blocks($escaped));
    }

    /**
     * Replace every match with a token so later passes cannot read inside it.
     *
     * @param  callable(array<int, string>): string  $render
     */
    private function lift(string $text, string $pattern, callable $render): string
    {
        return (string) preg_replace_callback($pattern, function (array $match) use ($render): string {
            $this->spans[] = $render($match);

            return self::TOKEN.(count($this->spans) - 1).self::TOKEN;
        }, $text);
    }

    private function restore(string $html): string
    {
        return (string) preg_replace_callback('~'.self::TOKEN.'(\d+)'.self::TOKEN.'~', fn (array $m): string => $this->spans[(int) $m[1]] ?? '', $html);
    }

    private function math(string $tex, string $mode): string
    {
        return '<span class="'.ChatFormat::MATH_CLASS.'" '.ChatFormat::MATH_ATTRIBUTE.'="'.$mode.'">'.e(trim($tex)).'</span>';
    }

    /**
     * @param  string  $url  the decoded URL
     * @param  string  $label  already-escaped link text
     */
    private function link(string $url, string $label): string
    {
        if (! ChatSanitiser::safeUrl($url)) {
            return $label;
        }

        return '<a href="'.e($url).'" '.self::LINK_ATTRIBUTES.'>'.$label.'</a>';
    }

    /**
     * Lines that start with a bullet or a number form lists; every other line
     * keeps its own line break. Inline markers are read per line.
     */
    private function blocks(string $escaped): string
    {
        $out = '';
        $lines = explode("\n", $escaped);
        $count = count($lines);
        $i = 0;
        $afterBlock = true; // nothing yet, so the first line needs no break before it

        while ($i < $count) {
            $kind = $this->listKind($lines[$i]);

            if ($kind === null) {
                $out .= ($afterBlock ? '' : "\n").$this->inline($lines[$i]);
                $afterBlock = false;
                $i++;

                continue;
            }

            $items = [];

            while ($i < $count && $this->listKind($lines[$i]) === $kind) {
                preg_match($kind === 'ul' ? self::BULLET_LINE : self::NUMBERED_LINE, $lines[$i], $match);
                $items[] = '<li>'.$this->inline($match[1]).'</li>';
                $i++;
            }

            $out = rtrim($out, "\n").'<'.$kind.'>'.implode('', $items).'</'.$kind.'>';
            $afterBlock = true;
        }

        return $out;
    }

    private function listKind(string $line): ?string
    {
        if (preg_match(self::BULLET_LINE, $line) === 1) {
            return 'ul';
        }

        return preg_match(self::NUMBERED_LINE, $line) === 1 ? 'ol' : null;
    }

    private function inline(string $text): string
    {
        $text = (string) preg_replace('~\*\*(?=\S)(.+?)(?<=\S)\*\*~u', '<strong>$1</strong>', $text);
        $text = (string) preg_replace('~(?<![\w*])\*(?=[^\s*])(.+?)(?<=[^\s*])\*(?![\w*])~u', '<em>$1</em>', $text);

        return (string) preg_replace('~(?<![\w])_(?=[^\s_])(.+?)(?<=[^\s_])_(?![\w])~u', '<em>$1</em>', $text);
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
