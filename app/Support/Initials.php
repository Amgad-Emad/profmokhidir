<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The one or two initials shown in a member's small ring avatar ("Amina
 * Hassan" → "AH"), taken from the first and last words of the name. Works for
 * any script, so an Arabic name gives Arabic initials.
 */
final class Initials
{
    public static function of(?string $name): string
    {
        $words = array_values(array_filter(preg_split('/\s+/u', trim((string) $name)) ?: [], fn (string $word): bool => $word !== ''));

        if ($words === []) {
            return '';
        }

        $picked = count($words) === 1 ? [$words[0]] : [$words[0], $words[count($words) - 1]];

        return implode('', array_map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)), $picked));
    }
}
