<?php

declare(strict_types=1);

namespace App\Constants;

use App\Services\LocaleService;

/**
 * Site-wide search: the route, the query limits, the fulltext behaviour and
 * the sections searched, in navigation order. The dialog in the header and
 * the plain /search page share these values.
 */
final class Search
{
    public const string ROUTE = 'search';

    public const string PATH = '/search';

    /** Query-string parameter carrying the search terms. */
    public const string PARAM = 'q';

    /** Shortest query that is searched; anything shorter shows the prompt instead. */
    public const int MIN_LENGTH = 2;

    /** Longest query accepted (longer input is cut, not rejected). */
    public const int MAX_LENGTH = 100;

    /** InnoDB's default innodb_ft_min_token_size: shorter words fall back to LIKE. */
    public const int FULLTEXT_MIN_TOKEN = 3;

    /**
     * InnoDB's default stopword list: words the fulltext index never holds, so
     * they are matched with LIKE instead of being required by the index.
     *
     * @return list<string>
     */
    public static function stopwords(): array
    {
        return [
            'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'i', 'in',
            'is', 'it', 'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when', 'where', 'who',
            'will', 'with', 'und', 'www',
        ];
    }

    public static function isIndexed(string $token): bool
    {
        return mb_strlen($token) >= self::FULLTEXT_MIN_TOKEN && ! in_array($token, self::stopwords(), true);
    }

    /** Results per section in the header dialog. */
    public const int DIALOG_PER_SECTION = 4;

    /** Results per section on the /search page. */
    public const int PAGE_PER_SECTION = 10;

    /** Characters of context shown around a match in a summary. */
    public const int EXCERPT_LENGTH = 160;

    /** Class on the <mark> that highlights matched words. */
    public const string HIGHLIGHT_CLASS = 'search-mark';

    /** Copy. */
    public const string TITLE = 'Search';

    public const string LABEL = 'Search the archive';

    public const string PLACEHOLDER = 'Publications, essays, lectures, awards…';

    public const string BUTTON_LABEL = 'Search';

    public const string CLOSE_LABEL = 'Close';

    public const string PROMPT = 'Type a word or two to search across every chapter of the site.';

    public const string NONE_FOUND = 'Nothing in the archive answers to that. Try another word, or browse the chapters.';

    public const string NONE_ATTRIBUTION = 'The archivist';

    public const string MEMBERS_NOTE = 'Members of the Scientific Community also see their teaching files here when signed in.';

    /** Keyboard shortcuts that open the dialog (WAI-ARIA aria-keyshortcuts syntax). */
    public const string KEY_SLASH = '/';

    public const string KEY_COMMAND = 'Meta+K';

    public const string KEY_CONTROL = 'Control+K';

    /**
     * The sections searched, in navigation order, with the public section each
     * belongs to and how a hit links back to the page.
     *
     * @return list<string>
     */
    public static function sections(): array
    {
        return [
            Sections::CV, Sections::PUBLICATIONS, Sections::AWARDS, Sections::VIDEOS,
            Sections::PICTURES, Sections::TEACHING, Sections::COMMENTARY, Sections::OTHER,
        ];
    }

    public static function isSearched(string $section): bool
    {
        return in_array($section, self::sections(), true);
    }

    /**
     * aria-keyshortcuts value for the search button.
     */
    public static function keyShortcuts(): string
    {
        return implode(' ', [self::KEY_SLASH, self::KEY_COMMAND, self::KEY_CONTROL]);
    }

    /**
     * "12 results in 3 sections" for the status line and screen readers.
     */
    public static function summary(int $results, int $sections): string
    {
        if ($results === 0) {
            return __('No results');
        }

        $digits = app(LocaleService::class);

        return __(':results in :sections', [
            'results' => trans_choice('{1} :count result|[2,*] :count results', $results, ['count' => $digits->digits($results)]),
            'sections' => trans_choice('{1} :count section|[2,*] :count sections', $sections, ['count' => $digits->digits($sections)]),
        ]);
    }
}
