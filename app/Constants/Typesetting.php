<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Typographic detail, in the manner of a university press: the 8px baseline
 * rhythm, the reading measure, the OpenType features each context switches on,
 * and the characters App\Services\TypesetterService puts into database text
 * (curly quotes, en dashes, non-breaking spaces). The CSS in resources/css/app.css
 * implements the rhythm, measure and features; keep the two in step.
 */
final class Typesetting
{
    /** Every vertical space is a multiple of this. Body lines are 32px (four units). */
    public const int BASELINE_PX = 8;

    public const int BODY_LINE_PX = 32;

    /** Reading measure in characters per line (the CSS measure is tuned to land inside it). */
    public const int MEASURE_MIN_CHARS = 60;

    public const int MEASURE_MAX_CHARS = 72;

    /** Drop caps span this many lines of the paragraph they open. */
    public const int DROP_CAP_LINES = 3;

    /**
     * <x-drop-cap> in Arabic: letters join, so instead of a dropped initial the first word of the first
     * paragraph is wrapped in this class and set in Amiri 700 (resources/css/locale.css).
     */
    public const string DROP_CAP_LEAD_CLASS = 'drop-cap__lead';

    /**
     * <x-icon> names that point along the line of text and so flip on right-to-left pages (they carry
     * .rtl-mirror): arrows, chevrons, reply, send, undo/redo, the sidebar collapse/expand and panel
     * icons, lists and the external-link arrow. Chevron-down is included so its rotated (sideways)
     * states point the right way. Never the MK mark, logos, media, charts, clocks or checks.
     *
     * @var list<string>
     */
    public const array MIRRORED_ICONS = [
        'arrow', 'chevron-left', 'chevron-down', 'reply', 'send', 'undo', 'redo',
        'collapse', 'expand', 'panel', 'list-bullet', 'list-numbered', 'external',
    ];

    public const string LEFT_DOUBLE_QUOTE = "\u{201C}";

    public const string RIGHT_DOUBLE_QUOTE = "\u{201D}";

    public const string LEFT_SINGLE_QUOTE = "\u{2018}";

    /** Also the apostrophe. */
    public const string RIGHT_SINGLE_QUOTE = "\u{2019}";

    public const string EN_DASH = "\u{2013}";

    public const string ELLIPSIS = "\u{2026}";

    public const string NBSP = "\u{00A0}";

    /** Narrow no-break space: between a number and its unit, and between initials. */
    public const string NARROW_NBSP = "\u{202F}";

    /** Class the formatter puts on runs of capitals so they set in small caps (c2sc) where the face has them. */
    public const string CAPS_CLASS = 'caps';

    /** Class on an opening quotation mark that hangs into the margin of a pull quote. */
    public const string HANG_CLASS = 'hang-quote';

    /**
     * Rhythm tokens: name => multiple of BASELINE_PX (the --space-* custom properties).
     *
     * @return array<string, int>
     */
    public static function rhythm(): array
    {
        return ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '6' => 6, '8' => 8, '12' => 12, '16' => 16];
    }

    /**
     * Units that take a narrow no-break space after their number ("12 ha", "35 °C").
     *
     * @return list<string>
     */
    public static function units(): array
    {
        return ['%', '°C', 'km', 'kg', 'ha', 'mm', 'cm', 'm', 'g', 't', 'ml', 'l', 'kB', 'MB', 'GB', 'mg', 'µm'];
    }

    /**
     * Abbreviated titles and references that keep their following word on the same line.
     *
     * @return list<string>
     */
    public static function bindingAbbreviations(): array
    {
        return ['Prof.', 'Dr.', 'Dr', 'Mr.', 'Mrs.', 'Ms.', 'St.', 'p.', 'pp.', 'vol.', 'no.', 'ch.', 'fig.', 'No.', 'Vol.'];
    }

    /**
     * Words that, written in capitals, stay as they are instead of becoming small caps.
     *
     * @return list<string>
     */
    public static function capsExceptions(): array
    {
        return ['I', 'II', 'III', 'A', 'MK'];
    }

    /**
     * OpenType settings per context: CSS selector summary => font-feature/variant settings.
     * Shown on the style guide; resources/css/app.css applies them.
     *
     * @return array<string, array{context: string, css: string, features: list<string>}>
     */
    public static function features(): array
    {
        return [
            'running' => ['context' => 'Cormorant running text, headings and dates', 'css' => 'font-variant-numeric: oldstyle-nums proportional-nums; font-variant-ligatures: common-ligatures', 'features' => ['onum', 'pnum', 'liga', 'kern']],
            'tabular' => ['context' => 'Cormorant in tables and stats', 'css' => 'font-variant-numeric: lining-nums tabular-nums', 'features' => ['lnum', 'tnum']],
            'small-caps' => ['context' => 'Abbreviations, bylines, chapter numerals, epigraph attributions', 'css' => 'font-variant-caps: all-small-caps (or small-caps for names)', 'features' => ['smcp', 'c2sc']],
            'discretionary' => ['context' => 'Italic epigraphs and pull quotes only', 'css' => 'font-variant-ligatures: common-ligatures discretionary-ligatures', 'features' => ['dlig']],
            'hanging' => ['context' => 'Pull quotes', 'css' => 'hanging-punctuation: first last (opening quote hangs in other browsers)', 'features' => []],
            'sans-tabular' => ['context' => 'Hanken Grotesk in tables, counts and the admin', 'css' => 'font-variant-numeric: tabular-nums', 'features' => ['tnum']],
        ];
    }

    /**
     * Lower-case Roman numerals are never used; chapter numerals are capitals set in small caps.
     *
     * @return array<int, string>
     */
    public static function romanSymbols(): array
    {
        return [1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD', 100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL', 10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'];
    }
}
