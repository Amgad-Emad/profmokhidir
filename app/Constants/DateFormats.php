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

    /** "12 Mar 2021": content dates in admin tables (the date of a material). */
    public const string SHORT = 'j M Y';

    /** "12 March 2021, 14:05": the exact moment behind a relative date ("3 hours ago"). */
    public const string LONG_TIME = 'j F Y, H:i';

    /** "March 2021": when only the month matters. */
    public const string MONTH_YEAR = 'F Y';

    /** "Wednesday 22 October 2026": the date under the dashboard greeting, set in small caps. */
    public const string GREETING = 'l j F Y';

    /** Machine-readable value for <time datetime>. */
    public const string MACHINE = 'Y-m-d';

    /** Joins the parts of a metadata line ("Book · 1978 · Khartoum"). */
    public const string META_SEPARATOR = ' · ';

    /** Joins a year range with a closed-up en dash ("1969–1975", "1990–present"). */
    public const string RANGE_SEPARATOR = '–';

    /** Shown in place of an end year for an ongoing entry. */
    public const string PRESENT = 'present';
}
