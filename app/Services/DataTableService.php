<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\DataTable;
use App\Constants\DateFormats;
use App\Constants\ListDensity;
use App\Constants\ListToolbar;
use App\Models\User;
use App\Repositories\Contracts\ListPreferenceRepositoryInterface;
use App\Support\DataTable\Cell;
use App\Support\DataTable\Column;
use App\Support\DataTable\Row;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The logic behind the shared admin data table (x-admin.data-table and the
 * WithDataTable Livewire concern): which columns show for a user (remembered
 * per screen), the page size, ranges for shift-click selection, the footer's
 * "1–20 of 84" and page numbers, the cells of dates, and the description the
 * component draws.
 */
class DataTableService
{
    public function __construct(
        private readonly ListPreferenceRepositoryInterface $preferences,
        private readonly LocaleService $locales,
    ) {}

    /**
     * The user's column choices for a screen (key => shown), [] for the defaults.
     *
     * @return array<string, bool>
     */
    public function columnChoices(?User $user, string $screen): array
    {
        $stored = $user === null ? null : $this->preferences->find($user, $screen)?->columns;

        return is_array($stored) ? array_map('boolval', array_filter($stored, 'is_bool')) : [];
    }

    /**
     * The columns that show, in order: the primary always, the rest by the
     * user's choice or, when they have not chosen, the column's default.
     *
     * @param  list<Column>  $columns
     * @param  array<string, bool>  $choices
     * @return list<Column>
     */
    public function visibleColumns(array $columns, array $choices): array
    {
        return array_values(array_filter(
            $columns,
            fn (Column $column): bool => ! $column->hideable() || ($choices[$column->key] ?? $column->visible),
        ));
    }

    /**
     * Show or hide one column for the user and remember it; returns the new choices.
     * Unknown keys and the primary column are ignored.
     *
     * @param  list<Column>  $columns
     * @param  array<string, bool>  $choices
     * @return array<string, bool>
     */
    public function setColumnVisible(?User $user, string $screen, array $columns, array $choices, string $key, bool $visible): array
    {
        $column = $this->column($columns, $key);

        if ($column === null || ! $column->hideable()) {
            return $choices;
        }

        $known = array_map(fn (Column $column): string => $column->key, $columns);
        $choices = array_intersect_key($choices, array_flip($known));
        $choices[$key] = $visible;

        if ($user !== null) {
            $this->preferences->saveTable($user, $screen, $choices);
        }

        return $choices;
    }

    /**
     * Back to each column's default, for the user too.
     */
    public function resetColumns(?User $user, string $screen): void
    {
        if ($user !== null) {
            $this->preferences->saveTable($user, $screen, []);
        }
    }

    public function normalisePerPage(int|string|null $perPage): int
    {
        $perPage = (int) $perPage;

        return in_array($perPage, DataTable::PAGE_SIZES, true) ? $perPage : DataTable::DEFAULT_PAGE_SIZE;
    }

    /**
     * The page size the user last chose on the screen (the default when none).
     */
    public function rememberedPerPage(?User $user, string $screen): int
    {
        return $this->normalisePerPage($user === null ? null : $this->preferences->find($user, $screen)?->per_page);
    }

    public function setPerPage(?User $user, string $screen, int|string $perPage): int
    {
        $perPage = $this->normalisePerPage($perPage);

        if ($user !== null) {
            $this->preferences->saveTable($user, $screen, null, $perPage);
        }

        return $perPage;
    }

    /**
     * The ids from one row to another in the order the page shows them, both
     * included and in either direction (shift-click selection). An id not on
     * the page gives just the other one.
     *
     * @param  list<int|string>  $order
     * @return list<int>
     */
    public function range(array $order, int|string $from, int|string $to): array
    {
        $order = array_map('intval', $order);
        $start = array_search((int) $from, $order, true);
        $end = array_search((int) $to, $order, true);

        if ($end === false) {
            return [];
        }

        if ($start === false) {
            return [$order[$end]];
        }

        return array_slice($order, min($start, $end), abs($end - $start) + 1);
    }

    /**
     * The footer's "1–20 of 84" (with the site's digits) and its parts.
     *
     * @return array{from: int, to: int, total: int, label: string}
     */
    public function summary(LengthAwarePaginator $page): array
    {
        $total = $page->total();
        $from = $total === 0 ? 0 : (($page->currentPage() - 1) * $page->perPage()) + 1;
        $to = min($total, $page->currentPage() * $page->perPage());

        return [
            'from' => $from,
            'to' => $to,
            'total' => $total,
            'label' => __('data-table.range', [
                'from' => $this->locales->digits($from),
                'to' => $this->locales->digits($to),
                'total' => $this->locales->digits($total),
            ]),
        ];
    }

    /**
     * Page numbers for the footer: the first, the last and `window` either
     * side of the current page, with null where pages are skipped.
     *
     * @return list<int|null>
     */
    public function pages(LengthAwarePaginator $page, int $window = 1): array
    {
        $last = $page->lastPage();
        $current = $page->currentPage();
        $pages = [];
        $previous = 0;

        for ($number = 1; $number <= $last; $number++) {
            if ($number !== 1 && $number !== $last && abs($number - $current) > $window) {
                continue;
            }

            if ($number - $previous > 1) {
                $pages[] = null;
            }

            $pages[] = $number;
            $previous = $number;
        }

        return $pages;
    }

    /**
     * A moment as "30 minutes ago", with the exact date and time in the tooltip.
     */
    public function relative(?CarbonInterface $moment): Cell
    {
        if ($moment === null) {
            return new Cell;
        }

        return new Cell(
            text: $this->locales->digits($moment->diffForHumans()),
            tooltip: $this->locales->digits($moment->translatedFormat(DateFormats::LONG_TIME)),
            datetime: $moment->toIso8601String(),
        );
    }

    /**
     * A content date in the short local format ("28 Oct 2011"), or a bare year.
     */
    public function date(CarbonInterface|int|string|null $date): Cell
    {
        if ($date === null || $date === '') {
            return new Cell;
        }

        if (! $date instanceof CarbonInterface) {
            return new Cell(text: (string) $date, datetime: (string) $date);
        }

        return new Cell(
            text: $date->translatedFormat(DateFormats::SHORT),
            tooltip: $date->translatedFormat(DateFormats::LONG),
            datetime: $date->format(DateFormats::MACHINE),
        );
    }

    /**
     * Everything x-admin.data-table draws.
     *
     * @param  list<Column>  $columns  every column the screen declares
     * @param  array<string, bool>  $choices
     * @param  list<Row>  $rows
     * @param  array<string, mixed>  $options  caption, selectable, selected, reordering, canReorder,
     *                                         sort, direction, density, matching, sortable (list of keys)
     * @return array<string, mixed>
     */
    public function describe(array $columns, array $choices, array $rows, ?LengthAwarePaginator $page, array $options = []): array
    {
        $visible = $this->visibleColumns($columns, $choices);
        $sortable = $options['sortable'] ?? null;
        $selected = array_map('intval', $options['selected'] ?? []);
        $pageIds = array_values(array_map(fn (Row $row): int => (int) $row->id, array_filter($rows, fn (Row $row): bool => $row->selectable)));
        $reordering = (bool) ($options['reordering'] ?? false);
        $selectable = (bool) ($options['selectable'] ?? false) && ! $reordering;
        $pageSelected = $pageIds !== [] && array_diff($pageIds, $selected) === [];
        $total = $page?->total() ?? count($rows);

        return [
            'caption' => (string) ($options['caption'] ?? ''),
            'columns' => array_map(fn (Column $column): array => [
                'column' => $column,
                'sortable' => $column->sortable && ($sortable === null || in_array($column->key, $sortable, true)) && ! $reordering,
            ], $visible),
            'menuColumns' => array_values(array_map(fn (Column $column): array => [
                'key' => $column->key,
                'label' => $column->label,
                'visible' => in_array($column, $visible, true),
            ], array_filter($columns, fn (Column $column): bool => $column->hideable()))),
            'rows' => $rows,
            'page' => $page,
            'summary' => $page === null ? null : $this->summary($page),
            'pages' => $page === null ? [] : $this->pages($page),
            'perPage' => $page?->perPage() ?? DataTable::DEFAULT_PAGE_SIZE,
            'pageSizes' => DataTable::PAGE_SIZES,
            'sort' => (string) ($options['sort'] ?? ''),
            'direction' => (string) ($options['direction'] ?? ListToolbar::SORT_ASC),
            'selectable' => $selectable,
            'selected' => $selected,
            'pageIds' => $pageIds,
            'pageSelected' => $pageSelected,
            'matching' => (int) ($options['matching'] ?? $total),
            'allMatchingSelected' => $selectable && $total > 0 && count($selected) >= $total,
            'offerSelectAll' => $selectable && $pageSelected && $total > count($pageIds) && count($selected) < $total && $total <= DataTable::MAX_SELECT_ALL,
            'reordering' => $reordering,
            'canReorder' => (bool) ($options['canReorder'] ?? false),
            'reorderable' => (bool) ($options['reorderable'] ?? false),
            'density' => (string) ($options['density'] ?? ListDensity::DEFAULT->value),
            'hasActions' => (bool) ($options['hasActions'] ?? true),
            'skeleton' => max(1, min(DataTable::SKELETON_ROWS, count($rows))),
            'id' => (string) ($options['id'] ?? 'data-table'),
        ];
    }

    /**
     * @param  list<Column>  $columns
     */
    private function column(array $columns, string $key): ?Column
    {
        foreach ($columns as $column) {
            if ($column->key === $key) {
                return $column;
            }
        }

        return null;
    }
}
