<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The shared admin data table (x-admin.data-table, the WithDataTable Livewire
 * concern and DataTableService): page sizes, limits, column priorities and the
 * address parameter of the page size.
 */
final class DataTable
{
    /** Rows per page the footer offers; the first is the default. */
    public const array PAGE_SIZES = [20, 50, 100];

    public const int DEFAULT_PAGE_SIZE = 20;

    /** Address parameter of a page size other than the default. */
    public const string PARAM_PER_PAGE = 'per_page';

    /** "Select all n matching" ticks at most this many rows. */
    public const int MAX_SELECT_ALL = 1000;

    /** Rows of the loading skeleton (fewer when the page holds fewer). */
    public const int SKELETON_ROWS = 8;

    /** Column priorities: 1 shows in the phone card too, 2 from tablet width, 3 from desktop width. */
    public const int PRIORITY_ESSENTIAL = 1;

    public const int PRIORITY_TABLET = 2;

    public const int PRIORITY_DESKTOP = 3;

    /** The media library shows its images as a grid of tiles or as a table. */
    public const string LAYOUT_GRID = 'grid';

    public const string LAYOUT_LIST = 'list';

    /** Supporting line parts are joined with this. */
    public const string SEPARATOR = ' · ';

    /**
     * @return list<int>
     */
    public static function pageSizes(): array
    {
        return self::PAGE_SIZES;
    }
}
