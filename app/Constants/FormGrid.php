<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The form grid every form lays its fields out on (resources/css/forms.css). `.form-grid`
 * stacks the parts of a form with one vertical gap (ROW_GAP_REM); `.form-row` is one row of
 * fields side by side, 12 columns from BREAKPOINT_PX and one column below it. A field spans
 * the full row unless it carries one of the width classes; short fields (phone, year,
 * postcode) take a half or a third, long ones and textareas the full row. Each field spans
 * ROWS_PER_FIELD rows as a subgrid (label, hint, control, counter, message), so the controls
 * of fields side by side share one top edge. The browser check (tests/qa/danger-forms.mjs)
 * measures it on every form.
 */
final class FormGrid
{
    /** The form's stack of fields and rows. */
    public const string GRID = 'form-grid';

    /** One row of fields side by side. */
    public const string ROW = 'form-row';

    /** A small button beside a row's fields (Remove), on the controls' line. */
    public const string ACTION = 'form-col--action';

    public const int COLUMNS = 12;

    /** Below this width every field takes the full row. */
    public const int BREAKPOINT_PX = 768;

    /** The rows of one field's subgrid: label, hint, control, counter, message. */
    public const int ROWS_PER_FIELD = 5;

    /** Space between the rows of a form, in rem (--form-row-gap). */
    public const float ROW_GAP_REM = 1.75;

    public const string FULL = 'full';

    public const string TWO_THIRDS = 'two-thirds';

    public const string HALF = 'half';

    public const string THIRD = 'third';

    public const string QUARTER = 'quarter';

    /**
     * Width name => columns spanned from the breakpoint up.
     *
     * @return array<string, int>
     */
    public static function widths(): array
    {
        return [
            self::FULL => 12,
            self::TWO_THIRDS => 8,
            self::HALF => 6,
            self::THIRD => 4,
            self::QUARTER => 3,
        ];
    }

    /**
     * The class that gives a field a width ("form-col--half"); the full row needs none.
     */
    public static function widthClass(string $width): string
    {
        if (! array_key_exists($width, self::widths())) {
            throw new \InvalidArgumentException("Unknown form width [{$width}]; use one of: ".implode(', ', array_keys(self::widths())).'.');
        }

        return $width === self::FULL ? '' : 'form-col--'.$width;
    }
}
