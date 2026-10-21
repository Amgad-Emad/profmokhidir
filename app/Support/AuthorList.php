<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\Catalogue;

/**
 * Reads the free-text authors field of a publication ("Khidir, M. O.; Smith, J."
 * or "M. O. Khidir and J. Smith") into names with a family and given part, so
 * citations can be written in each style and the Professor's name picked out.
 */
final class AuthorList
{
    /**
     * @return list<array{raw: string, family: string, given: string}>
     */
    public static function parse(?string $authors): array
    {
        if ($authors === null || trim($authors) === '') {
            return [];
        }

        $names = [];

        foreach (preg_split(Catalogue::AUTHOR_SEPARATOR_PATTERN, trim($authors)) ?: [] as $part) {
            // Trailing full stops belong to initials ("M. O."), so only spaces and commas are trimmed.
            $raw = trim($part, " \t\n\r\0\x0B,");

            if ($raw === '') {
                continue;
            }

            $names[] = ['raw' => $raw] + self::split($raw);
        }

        return $names;
    }

    /**
     * "Khidir, M. O." => family Khidir, given "M. O."; "Mohamed Osman Khidir" => family Khidir.
     *
     * @return array{family: string, given: string}
     */
    public static function split(string $name): array
    {
        if (str_contains($name, ',')) {
            [$family, $given] = array_map('trim', explode(',', $name, 2));

            return ['family' => $family, 'given' => $given];
        }

        $words = preg_split('/\s+/u', trim($name)) ?: [];
        $family = (string) array_pop($words);

        return ['family' => $family, 'given' => implode(' ', $words)];
    }

    /**
     * Initials of the given names: "Mohamed Osman" => "M. O.", "M.O." => "M. O.".
     */
    public static function initials(string $given): string
    {
        preg_match_all('/\p{L}+(?:-\p{L}+)?/u', $given, $words);

        return implode(' ', array_map(
            static fn (string $word): string => implode('-', array_map(
                static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)).'.',
                explode('-', $word),
            )),
            $words[0],
        ));
    }

    /**
     * "Given Family" (MLA and Chicago second and later authors), or the family name alone.
     *
     * @param  array{family: string, given: string}  $author
     */
    public static function givenFirst(array $author): string
    {
        return trim($author['given'].' '.$author['family']);
    }

    /**
     * "Family, Given", or the family name alone.
     *
     * @param  array{family: string, given: string}  $author
     */
    public static function familyFirst(array $author): string
    {
        return $author['given'] === '' ? $author['family'] : $author['family'].', '.$author['given'];
    }

    /**
     * Whether this author is the Professor.
     *
     * @param  array{family: string, given: string}  $author
     */
    public static function isSelf(array $author): bool
    {
        return mb_strtolower($author['family']) === mb_strtolower(Catalogue::SELF_FAMILY_NAME);
    }
}
