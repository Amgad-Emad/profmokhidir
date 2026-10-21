<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The long-form reading layout of a commentary: reading time, the table of
 * contents built from its headings, sidenotes from the editor's footnote
 * markers and the "Further reading" list. App\Services\EssayService applies them.
 */
final class Essay
{
    /** Unhurried reading pace used for "n min read". */
    public const int WORDS_PER_MINUTE = 220;

    public const string READING_TIME_SUFFIX = 'min read';

    /** Headings that enter the table of contents (h2 and h3 of the body). */
    public const array TOC_LEVELS = [2, 3];

    /** The table of contents is shown only when the essay has at least this many headings. */
    public const int TOC_MIN_HEADINGS = 2;

    public const string TOC_LABEL = 'Contents';

    /** Prefix of heading ids, so they never collide with the page's own ids. */
    public const string HEADING_ID_PREFIX = 'part-';

    /**
     * A footnote marker in the body, as the editor types it: "[^1]", "[^note]".
     * Its note is a paragraph of its own starting with the same marker and a
     * colon: "[^1]: The note text." The note becomes a sidenote at the marker.
     */
    public const string FOOTNOTE_MARKER_PATTERN = '/\[\^([\w-]{1,20})\]/u';

    public const string FOOTNOTE_DEFINITION_PATTERN = '#<p>\s*\[\^([\w-]{1,20})\]:\s*(.*?)</p>#su';

    /** Related commentaries (sharing a topic tag) listed under "Further reading". */
    public const int FURTHER_READING_LIMIT = 3;

    public const string FURTHER_READING_LABEL = 'Further reading';
}
