<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CitationStyle;
use App\Constants\PublicationType;
use App\Constants\Typesetting;
use App\Models\Publication;
use App\Support\AuthorList;
use Illuminate\Support\Str;

/**
 * Writes a publication's reference in each CitationStyle: APA (7th), MLA (9th),
 * Chicago (17th, bibliography) and BibTeX. Prose styles come as plain text (for
 * the clipboard) and as escaped HTML with the italic parts in <i>.
 *
 * Works inside a container (articles, chapters, papers) have their title in
 * quotation marks and the container in italics; stand-alone works (books,
 * reports, other) have their title in italics and the publisher in roman.
 *
 * @phpstan-type Segment array{0: string, 1: bool}
 * @phpstan-type Citation array{style: CitationStyle, label: string, edition: string, text: string, html: string, code: bool}
 */
class CitationFormatterService
{
    /** Words skipped when choosing the title word of a BibTeX key. */
    private const array KEY_STOP_WORDS = ['a', 'an', 'the', 'on', 'of', 'in', 'and', 'for', 'to', 'placeholder'];

    /** APA lists every author up to this many. */
    private const int APA_MAX_AUTHORS = 20;

    /** Chicago lists every author up to this many, then the first seven and "et al.". */
    private const int CHICAGO_MAX_AUTHORS = 10;

    private const int CHICAGO_SHORTENED_AUTHORS = 7;

    /**
     * Every style for one publication, in CitationStyle order.
     *
     * @return list<Citation>
     */
    public function all(Publication $publication): array
    {
        return array_map(fn (CitationStyle $style): array => [
            'style' => $style,
            'label' => $style->label(),
            'edition' => $style->edition(),
            'text' => $this->format($publication, $style),
            'html' => $this->html($publication, $style),
            'code' => $style->isCode(),
        ], CitationStyle::cases());
    }

    /**
     * The reference as plain text.
     */
    public function format(Publication $publication, CitationStyle $style): string
    {
        if ($style === CitationStyle::Bibtex) {
            return $this->bibtex($publication);
        }

        return implode('', array_map(fn (array $segment): string => $segment[0], $this->segments($publication, $style)));
    }

    /**
     * The reference as escaped HTML, italic parts in <i>.
     */
    public function html(Publication $publication, CitationStyle $style): string
    {
        if ($style === CitationStyle::Bibtex) {
            return e($this->bibtex($publication));
        }

        return implode('', array_map(
            fn (array $segment): string => $segment[1] ? '<i>'.e($segment[0]).'</i>' : e($segment[0]),
            $this->segments($publication, $style),
        ));
    }

    /**
     * @return list<Segment>
     */
    private function segments(Publication $publication, CitationStyle $style): array
    {
        return match ($style) {
            CitationStyle::Apa => $this->apa($publication),
            CitationStyle::Mla => $this->mla($publication),
            CitationStyle::Chicago => $this->chicago($publication),
            CitationStyle::Bibtex => [[$this->bibtex($publication), false]],
        };
    }

    /**
     * Khidir, M. O., & Smith, J. (1978). Title. <i>Venue</i>. https://doi.org/…
     *
     * @return list<Segment>
     */
    private function apa(Publication $publication): array
    {
        $authors = AuthorList::parse($publication->authors);
        $names = array_map(
            fn (array $author): string => trim($author['family'].($author['given'] !== '' ? ', '.AuthorList::initials($author['given']) : '')),
            array_slice($authors, 0, self::APA_MAX_AUTHORS),
        );

        $segments = [];
        $date = '('.($publication->year ?? 'n.d.').').';

        if ($names !== []) {
            $list = count($names) === 1
                ? $names[0]
                : implode(', ', array_slice($names, 0, -1)).', & '.end($names);
            $segments[] = [$this->sentence($list).' '.$date.' ', false];
        }

        $title = $this->title($publication);

        if ($this->isContained($publication)) {
            $segments[] = [$this->sentence($title), false];
            if ($names === []) {
                $segments[] = [' '.$date, false];
            }
            if ($this->venue($publication) !== null) {
                $segments[] = [$publication->type === PublicationType::Chapter ? ' In ' : ' ', false];
                $segments[] = [$this->venue($publication), true];
                $segments[] = ['.', false];
            }
        } else {
            array_push($segments, ...$this->italicSentence($title));
            if ($names === []) {
                $segments[] = [' '.$date, false];
            }
            if ($this->venue($publication) !== null) {
                $segments[] = [' '.$this->sentence($this->venue($publication)), false];
            }
        }

        if ($this->link($publication) !== null) {
            $segments[] = [' '.$this->link($publication), false];
        }

        return $segments;
    }

    /**
     * Khidir, Mohamed, and John Smith. “Title.” <i>Venue</i>, 1978, https://doi.org/….
     *
     * @return list<Segment>
     */
    private function mla(Publication $publication): array
    {
        $authors = AuthorList::parse($publication->authors);
        $segments = [];

        if ($authors !== []) {
            $list = match (count($authors)) {
                1 => AuthorList::familyFirst($authors[0]),
                2 => AuthorList::familyFirst($authors[0]).', and '.AuthorList::givenFirst($authors[1]),
                default => AuthorList::familyFirst($authors[0]).', et al',
            };
            $segments[] = [$this->sentence($list).' ', false];
        }

        $tail = array_values(array_filter([
            $publication->year !== null ? (string) $publication->year : null,
            $this->link($publication),
        ]));

        if ($this->isContained($publication)) {
            $segments[] = [$this->quoted($this->title($publication)), false];

            if ($this->venue($publication) !== null) {
                $segments[] = [' ', false];
                $segments[] = [$this->venue($publication), true];
                $segments[] = [$tail === [] ? '.' : ', '.$this->sentence(implode(', ', $tail)), false];
            } elseif ($tail !== []) {
                $segments[] = [' '.$this->sentence(implode(', ', $tail)), false];
            }
        } else {
            array_push($segments, ...$this->italicSentence($this->title($publication)));
            $rest = array_values(array_filter([$this->venue($publication), ...$tail]));

            if ($rest !== []) {
                $segments[] = [' '.$this->sentence(implode(', ', $rest)), false];
            }
        }

        return $segments;
    }

    /**
     * Khidir, Mohamed, and John Smith. “Title.” <i>Venue</i> (1978). https://doi.org/….
     *
     * @return list<Segment>
     */
    private function chicago(Publication $publication): array
    {
        $authors = AuthorList::parse($publication->authors);
        $segments = [];

        if ($authors !== []) {
            $shortened = count($authors) > self::CHICAGO_MAX_AUTHORS;
            $listed = $shortened ? array_slice($authors, 0, self::CHICAGO_SHORTENED_AUTHORS) : $authors;
            $names = [AuthorList::familyFirst($listed[0])];

            foreach (array_slice($listed, 1) as $author) {
                $names[] = AuthorList::givenFirst($author);
            }

            $list = match (true) {
                $shortened => implode(', ', $names).', et al',
                count($names) === 1 => $names[0],
                count($names) === 2 => $names[0].', and '.$names[1],
                default => implode(', ', array_slice($names, 0, -1)).', and '.end($names),
            };
            $segments[] = [$this->sentence($list).' ', false];
        }

        $link = $this->link($publication);

        if ($this->isContained($publication)) {
            $segments[] = [$this->quoted($this->title($publication)), false];

            if ($this->venue($publication) !== null) {
                $segments[] = [$publication->type === PublicationType::Chapter ? ' In ' : ' ', false];
                $segments[] = [$this->venue($publication), true];
            }

            $segments[] = [$publication->year !== null ? ' ('.$publication->year.').' : ($this->venue($publication) !== null ? '.' : ''), false];
        } else {
            array_push($segments, ...$this->italicSentence($this->title($publication)));
            $rest = array_values(array_filter([$this->venue($publication), $publication->year !== null ? (string) $publication->year : null]));

            if ($rest !== []) {
                $segments[] = [' '.$this->sentence(implode(', ', $rest)), false];
            }
        }

        if ($link !== null) {
            $segments[] = [' '.$link.'.', false];
        }

        return $segments;
    }

    private function bibtex(Publication $publication): string
    {
        [$entry, $venueField] = match ($publication->type) {
            PublicationType::Book => ['book', 'publisher'],
            PublicationType::JournalArticle => ['article', 'journal'],
            PublicationType::Chapter => ['incollection', 'booktitle'],
            PublicationType::ConferencePaper => ['inproceedings', 'booktitle'],
            PublicationType::Report => ['techreport', 'institution'],
            default => ['misc', 'howpublished'],
        };

        $authors = AuthorList::parse($publication->authors);
        $doi = filled($publication->doi) ? ltrim((string) $publication->doi, '/') : null;

        $fields = array_filter([
            'author' => $authors === [] ? null : $this->escapeBibtex(implode(' and ', array_map(fn (array $author): string => AuthorList::familyFirst($author), $authors))),
            'title' => $this->escapeBibtex($this->title($publication)),
            $venueField => $this->venue($publication) !== null ? $this->escapeBibtex($this->venue($publication)) : null,
            'year' => $publication->year !== null ? (string) $publication->year : null,
            'doi' => $doi,
            'url' => $doi === null && filled($publication->url) ? (string) $publication->url : null,
        ], fn (?string $value): bool => $value !== null && $value !== '');

        $lines = [];

        foreach ($fields as $name => $value) {
            $lines[] = '  '.$name.' = {'.$value.'}';
        }

        return '@'.$entry.'{'.$this->bibtexKey($publication, $authors).",\n".implode(",\n", $lines)."\n}";
    }

    /**
     * khidir1978principles: first author's family name, year and first significant title word, ASCII only.
     *
     * @param  list<array{raw: string, family: string, given: string}>  $authors
     */
    public function bibtexKey(Publication $publication, ?array $authors = null): string
    {
        $authors ??= AuthorList::parse($publication->authors);
        $family = $authors === [] ? 'anon' : $authors[0]['family'];

        $words = preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($this->title($publication)))) ?: [];
        $word = collect($words)->first(fn (string $word): bool => $word !== '' && ! in_array($word, self::KEY_STOP_WORDS, true)) ?? '';

        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($family))).($publication->year ?? '').$word;
    }

    private function escapeBibtex(string $value): string
    {
        return (string) preg_replace('/(?<!\\\\)([&%$#_])/', '\\\\$1', $value);
    }

    private function isContained(Publication $publication): bool
    {
        return in_array($publication->type, [PublicationType::JournalArticle, PublicationType::Chapter, PublicationType::ConferencePaper], true);
    }

    private function title(Publication $publication): string
    {
        return rtrim(trim((string) $publication->title), '.');
    }

    /**
     * Venue or publisher, without the "In:" an editor may have typed for a chapter.
     */
    private function venue(Publication $publication): ?string
    {
        $venue = trim((string) preg_replace('/^In:?\s+/i', '', trim((string) $publication->venue_publisher)));

        return $venue === '' ? null : rtrim($venue, '.');
    }

    private function link(Publication $publication): ?string
    {
        return $publication->doiUrl() ?? (filled($publication->url) ? (string) $publication->url : null);
    }

    /**
     * Ends the text with a full stop unless it already ends in . ? or !.
     */
    private function sentence(string $text): string
    {
        $text = rtrim($text);

        return preg_match('/[.?!]$/u', $text) === 1 ? $text : $text.'.';
    }

    /**
     * An italic title followed by a roman full stop (a title ending in ? or ! keeps its own, in italics).
     *
     * @return list<Segment>
     */
    private function italicSentence(string $title): array
    {
        return preg_match('/[.?!]$/u', $title) === 1 ? [[$title, true]] : [[$title, true], ['.', false]];
    }

    /**
     * “Title.” with the full stop inside the quotation marks (or the title's own ? or !).
     */
    private function quoted(string $title): string
    {
        return Typesetting::LEFT_DOUBLE_QUOTE.$this->sentence($title).Typesetting::RIGHT_DOUBLE_QUOTE;
    }
}
