<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\Search;
use Illuminate\Support\Str;

/**
 * Marks the searched words in a result. Text is escaped first, so the only
 * markup in the output is the <mark> around each match, which begins at a
 * word boundary and runs to the end of the word (the index matches prefixes).
 */
final class SearchHighlighter
{
    /**
     * @param  list<string>  $tokens
     */
    public function highlight(?string $text, array $tokens): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $pattern = $this->pattern($tokens);

        if ($pattern === null) {
            return e($text);
        }

        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            return e($text);
        }

        $html = '';

        foreach ($parts as $part) {
            $html .= preg_match($pattern, $part) === 1 && $this->isWholeMatch($pattern, $part)
                ? '<mark class="'.Search::HIGHLIGHT_CLASS.'">'.e($part).'</mark>'
                : e($part);
        }

        return $html;
    }

    /**
     * A short passage around the first match (or the opening of the text),
     * highlighted. Tags in editor HTML are stripped first.
     *
     * @param  list<string>  $tokens
     */
    public function excerpt(?string $text, array $tokens, int $length = Search::EXCERPT_LENGTH): string
    {
        $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

        if ($plain === '') {
            return '';
        }

        $pattern = $this->pattern($tokens);
        $start = 0;

        if ($pattern !== null && preg_match($pattern, $plain, $match, PREG_OFFSET_CAPTURE) === 1) {
            $position = mb_strlen(substr($plain, 0, $match[0][1]));
            $start = max(0, $position - intdiv($length, 3));
        }

        if ($start > 0) {
            // Begin at a word boundary.
            $space = mb_strpos($plain, ' ', $start);
            $start = $space === false ? $start : $space + 1;
        }

        $slice = mb_substr($plain, $start, $length);
        $truncated = $start + $length < mb_strlen($plain);

        if ($truncated) {
            $slice = Str::beforeLast($slice, ' ');
        }

        return ($start > 0 ? '… ' : '').$this->highlight($slice, $tokens).($truncated ? ' …' : '');
    }

    /**
     * @param  list<string>  $tokens
     */
    private function pattern(array $tokens): ?string
    {
        $tokens = array_values(array_filter(array_map('trim', $tokens), fn (string $token): bool => $token !== ''));

        if ($tokens === []) {
            return null;
        }

        usort($tokens, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        $alternatives = implode('|', array_map(fn (string $token): string => preg_quote($token, '/'), $tokens));

        return '/((?<![\p{L}\p{N}])(?:'.$alternatives.')[\p{L}\p{N}]*)/iu';
    }

    private function isWholeMatch(string $pattern, string $part): bool
    {
        return preg_replace($pattern, '', $part) === '';
    }
}
