<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The normalised text the site search matches against. Arabic is written in
 * many equivalent ways, so both the stored search column and the visitor's
 * query are folded the same way: diacritics (tashkeel) and the tatweel are
 * removed, the alef forms (أ إ آ ٱ) become ا, taa marbuta (ة) becomes ه and
 * alef maqsura (ى) becomes ي. Latin text is lower-cased; markup is dropped.
 */
final class SearchText
{
    /** Column on each searchable table holding the folded text of every language. */
    public const string COLUMN = 'search_text';

    public static function normalise(string $text): string
    {
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Harakat, tanween, shadda, sukun, superscript alef and Quranic marks; the tatweel.
        $text = (string) preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $text);

        $text = strtr($text, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ة' => 'ه',
            'ى' => 'ي',
        ]);

        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($text)));
    }

    /**
     * The search column's value for a set of field values (strings, lists, or
     * per-language maps of either), each language's text joined.
     *
     * @param  array<int|string, mixed>  $values
     */
    public static function from(array $values): string
    {
        $parts = [];

        array_walk_recursive($values, function (mixed $value) use (&$parts): void {
            if (is_string($value) && trim($value) !== '') {
                $parts[] = self::normalise($value);
            }
        });

        return implode(' ', $parts);
    }
}
