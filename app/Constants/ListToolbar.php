<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The list toolbar above every admin list (x-admin.list-toolbar, the
 * WithListToolbar Livewire concern and ListToolbarService): its view keys,
 * address parameters, timings and limits.
 */
final class ListToolbar
{
    /** The view every screen has: nothing filtered. */
    public const string VIEW_ALL = 'all';

    /** The trash view of the content screens (soft-deleted records). */
    public const string VIEW_TRASH = 'trash';

    /** Address parameters (the page is Livewire's own `page`). */
    public const string PARAM_SEARCH = 'q';

    public const string PARAM_FILTERS = 'filters';

    public const string PARAM_SORT = 'sort';

    public const string PARAM_DIRECTION = 'direction';

    public const string PARAM_TAB = 'tab';

    /** Multi-choice filters keep their values in one parameter, joined by this. */
    public const string VALUE_SEPARATOR = ',';

    /** The value of an on/off filter that is on. */
    public const string FLAG_ON = '1';

    public const string SORT_ASC = 'asc';

    public const string SORT_DESC = 'desc';

    /** Search runs as the editor types, this long after the last key. */
    public const int SEARCH_DEBOUNCE_MS = 300;

    /** A free-text filter value (e.g. a search term) is cut to this length before it is stored. */
    public const int MAX_VALUE_LENGTH = 100;

    /** Remembered searches are cut to this length. */
    public const int MAX_SEARCH_LENGTH = 200;

    /** A multi-choice button shows its first choice and "+n" for the rest. */
    public const int BUTTON_LABEL_CHOICES = 1;

    /** The Livewire event the toolbar's script listens for to keep the band in view. */
    public const string EVENT_FILTERED = 'list-filtered';

    /**
     * @return list<string>
     */
    public static function directions(): array
    {
        return [self::SORT_ASC, self::SORT_DESC];
    }
}
