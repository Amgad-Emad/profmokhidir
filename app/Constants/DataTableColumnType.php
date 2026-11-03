<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * What a data table column holds, which decides how its cells are drawn and
 * the edge they align to: text and badges at the start, numbers, dates and
 * status at the end. The primary column (title, supporting line, thumbnail and
 * small badges) is the one sticky column after the checkboxes.
 */
enum DataTableColumnType: string
{
    case Primary = 'primary';
    case Text = 'text';
    case Mono = 'mono';
    case Number = 'number';

    /** A content date (a year, the date of a material): the short local format. */
    case Date = 'date';

    /** A moment (updated, received): "30 minutes ago" with the exact time in a tooltip. */
    case Relative = 'relative';

    case Status = 'status';
    case Badges = 'badges';

    public function align(): DataTableAlign
    {
        return match ($this) {
            self::Number, self::Date, self::Relative, self::Status => DataTableAlign::End,
            default => DataTableAlign::Start,
        };
    }

    /**
     * Whether the column's first click sorts the largest or newest first.
     */
    public function descendsFirst(): bool
    {
        return in_array($this, [self::Number, self::Date, self::Relative], true);
    }
}
