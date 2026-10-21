<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The library-catalogue presentation of Publications and Other Information:
 * filter parameter names, the "Selected works" strip, decade grouping and how
 * the Professor's own name is picked out in an author list.
 */
final class Catalogue
{
    /** Query-string filters on /publications. */
    public const string FILTER_TYPE = 'type';

    public const string FILTER_FROM = 'from';

    public const string FILTER_TO = 'to';

    public const string FILTER_TOPIC = 'topic';

    /** Featured publications shown in the "Selected works" strip, at most. */
    public const int SELECTED_WORKS_LIMIT = 6;

    public const string SELECTED_WORKS_LABEL = 'Selected works';

    /** Records are grouped under decade headings ("1970s"); records with no year go last. */
    public const int DECADE = 10;

    public const string DECADE_SUFFIX = 's';

    public const string UNDATED_LABEL = 'Undated';

    /**
     * An author whose family name is this (case-insensitive) is the Professor,
     * set in small caps in the catalogue record.
     */
    public const string SELF_FAMILY_NAME = 'Khidir';

    /** Separators between authors in the free-text authors field. */
    public const string AUTHOR_SEPARATOR_PATTERN = '/\s*;\s*|\s+(?:and|&)\s+/u';

    /** Longest accepted topic, matching the publications.topic column. */
    public const int TOPIC_MAX = 100;

    /**
     * Years accepted by the year-range filter.
     */
    public const int YEAR_MIN = 1000;

    public const int YEAR_MAX = 9999;
}
