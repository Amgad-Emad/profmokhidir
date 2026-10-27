<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Essay;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

/**
 * Prepares a commentary body (already sanitised and typeset) for the long-form
 * reading layout: ids on its headings and a table of contents built from them,
 * the editor's footnote markers turned into sidenotes, and the reading time.
 *
 * Footnotes are written in the editor as a marker in the text, "[^1]", and a
 * paragraph of their own anywhere in the body, "[^1]: The note." Each marker
 * becomes a numbered <x-sidenote> holding its note, numbered in reading order;
 * the note paragraphs are removed and markers without a note are dropped.
 *
 * @phpstan-type ContentsEntry array{id: string, text: string, level: int}
 */
class EssayService
{
    /**
     * `$firstNote` is the number of the first sidenote, when the page already shows notes above the body.
     *
     * @return array{html: string, contents: list<ContentsEntry>, minutes: int, notes: int}
     */
    public function prepare(string $html, int $firstNote = 1): array
    {
        [$html, $notes] = $this->sidenotes($html, $firstNote);
        [$html, $contents] = $this->headings($html);

        return [
            'html' => $html,
            'contents' => count($contents) >= Essay::TOC_MIN_HEADINGS ? $contents : [],
            'minutes' => $this->readingMinutes($html),
            'notes' => $notes,
        ];
    }

    /**
     * Whole minutes at an unhurried pace, never less than one.
     */
    public function readingMinutes(string $html): int
    {
        $text = trim(html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($text === '') {
            return 1;
        }

        $words = count(preg_split('/\s+/u', $text) ?: []);

        return max(1, (int) ceil($words / Essay::WORDS_PER_MINUTE));
    }

    /**
     * "8 min read".
     */
    public function readingTime(string $html): string
    {
        return $this->readingLabel($this->readingMinutes($html));
    }

    /**
     * "8 min read" for a number of minutes, in the current language.
     */
    public function readingLabel(int $minutes): string
    {
        return trans_choice(Essay::READING_TIME, $minutes, ['count' => app(LocaleService::class)->digits($minutes)]);
    }

    /**
     * Give every h2 and h3 a unique id and list them for the table of contents.
     *
     * @return array{0: string, 1: list<ContentsEntry>}
     */
    public function headings(string $html): array
    {
        $levels = implode('', Essay::TOC_LEVELS);
        $contents = [];
        $used = [];

        $html = (string) preg_replace_callback(
            '#<h(['.$levels.'])>(.*?)</h\1>#su',
            function (array $match) use (&$contents, &$used): string {
                $text = trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($text === '') {
                    return $match[0];
                }

                $base = Essay::HEADING_ID_PREFIX.(Str::slug($text) ?: 'section');
                $id = $base;

                for ($n = 2; in_array($id, $used, true); $n++) {
                    $id = $base.'-'.$n;
                }

                $used[] = $id;
                $contents[] = ['id' => $id, 'text' => $text, 'level' => (int) $match[1]];

                return '<h'.$match[1].' id="'.e($id).'">'.$match[2].'</h'.$match[1].'>';
            },
            $html,
        );

        return [$html, $contents];
    }

    /**
     * Replace footnote markers with sidenotes and remove the note paragraphs.
     *
     * @return array{0: string, 1: int} the HTML and the number of sidenotes placed
     */
    public function sidenotes(string $html, int $firstNote = 1): array
    {
        $definitions = [];

        $html = (string) preg_replace_callback(Essay::FOOTNOTE_DEFINITION_PATTERN, function (array $match) use (&$definitions): string {
            $definitions[$match[1]] ??= trim($match[2]);

            return '';
        }, $html);

        $number = $firstNote - 1;

        $html = (string) preg_replace_callback(Essay::FOOTNOTE_MARKER_PATTERN, function (array $match) use ($definitions, &$number): string {
            $note = $definitions[$match[1]] ?? null;

            if ($note === null || $note === '') {
                return '';
            }

            $number++;

            return trim(Blade::render(
                '<x-sidenote :number="$number" :id="$id">{!! $note !!}</x-sidenote>',
                ['number' => $number, 'id' => 'note-'.$number, 'note' => $note],
            ));
        }, $html);

        return [trim($html), $number - $firstNote + 1];
    }
}
