<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The one way dates are written on the public site. Views render dates through
 * <x-date>, which uses these formats; nothing else formats a date by hand.
 */
final class DateFormats
{
    /** "12 March 2021": full dates in metadata lines and bylines. */
    public const string LONG = 'j F Y';

    /** "March 2021": when only the month matters. */
    public const string MONTH_YEAR = 'F Y';

    /** Machine-readable value for <time datetime>. */
    public const string MACHINE = 'Y-m-d';

    /** Joins the parts of a metadata line ("Book · 1978 · Khartoum"). */
    public const string META_SEPARATOR = ' · ';

    /** Joins a year range ("1969 – 1975"). */
    public const string RANGE_SEPARATOR = ' – ';

    /** Shown in place of an end year for an ongoing entry. */
    public const string PRESENT = 'present';
}
