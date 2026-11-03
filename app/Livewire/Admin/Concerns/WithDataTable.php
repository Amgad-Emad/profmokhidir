<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Concerns;

use App\Constants\DataTable;
use App\Constants\ListToolbar;
use App\Services\DataTableService;
use App\Support\DataTable\Column;
use App\Support\DataTable\Row;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;

/**
 * The state and actions behind x-admin.data-table on a Livewire list screen
 * (which also uses WithListToolbar and WithPagination): sortable headers, the
 * Columns menu (remembered per user and screen), the page size (in the address
 * and remembered), selection with shift-click ranges and "Select all n
 * matching", and the Reorder mode of screens kept in a display order.
 *
 * A screen declares its columns (Column), builds a Row per record and passes
 * dataTable($page, $rows) to the component. Screens with bulk actions declare
 * a public `$selected` array and dataTableMatchingIds(); screens kept in a
 * display order override dataTableReorderable(), dataTableCanReorder() and
 * dataTableSaveOrder().
 */
trait WithDataTable
{
    #[Url(as: DataTable::PARAM_PER_PAGE, except: DataTable::DEFAULT_PAGE_SIZE)]
    public int $perPage = DataTable::DEFAULT_PAGE_SIZE;

    /** @var array<string, bool> The user's shown or hidden columns (key => shown). */
    public array $columnChoices = [];

    public bool $reordering = false;

    /**
     * Every column of the table, in order.
     *
     * @return list<Column>
     */
    abstract protected function dataTableColumns(): array;

    /**
     * The ids of every record that matches the search and filters (for "Select all n matching").
     *
     * @return list<int>
     */
    protected function dataTableMatchingIds(): array
    {
        return [];
    }

    /**
     * Whether the screen has a display order the editor can change.
     */
    protected function dataTableReorderable(): bool
    {
        return false;
    }

    /**
     * Whether reordering is possible right now (no search, filter or sort).
     */
    protected function dataTableCanReorder(): bool
    {
        return false;
    }

    /**
     * Save the order of the rows on this page.
     *
     * @param  list<int>  $orderedIds
     */
    protected function dataTableSaveOrder(array $orderedIds): void {}

    public function mountWithDataTable(): void
    {
        $service = $this->dataTableService();
        $user = auth()->user();

        $this->columnChoices = $service->columnChoices($user, $this->listScreen());

        $this->perPage = request()->query(DataTable::PARAM_PER_PAGE) === null
            ? $service->rememberedPerPage($user, $this->listScreen())
            : $service->normalisePerPage($this->perPage);
    }

    /**
     * A sortable header: sort by its column, or turn the direction round.
     * Dates and numbers start with the newest or largest, text from A.
     */
    public function sortBy(string $column): void
    {
        $definition = $this->dataTableColumn($column);

        if ($definition === null || ! $definition->sortable || ! in_array($column, $this->listSortColumns(), true)) {
            return;
        }

        $direction = $this->sort === $column
            ? ($this->direction === ListToolbar::SORT_ASC ? ListToolbar::SORT_DESC : ListToolbar::SORT_ASC)
            : ($this->sortsNewestFirst($definition) ? ListToolbar::SORT_DESC : ListToolbar::SORT_ASC);

        $this->applySort($column, $direction);
    }

    public function setColumnVisible(string $key, bool $visible): void
    {
        $this->columnChoices = $this->dataTableService()->setColumnVisible(auth()->user(), $this->listScreen(), $this->dataTableColumns(), $this->columnChoices, $key, $visible);
    }

    public function resetColumns(): void
    {
        $this->columnChoices = [];
        $this->dataTableService()->resetColumns(auth()->user(), $this->listScreen());
    }

    public function setPerPage(int|string $perPage): void
    {
        $this->perPage = $this->dataTableService()->setPerPage(auth()->user(), $this->listScreen(), $perPage);
        $this->clearDataTableSelection();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->setPerPage($this->perPage);
    }

    /**
     * Tick or untick rows (a checkbox, or a shift-click range worked out in the browser).
     *
     * @param  list<int|string>  $ids
     */
    public function selectRows(array $ids, bool $on = true): void
    {
        if (! property_exists($this, 'selected')) {
            return;
        }

        $ids = array_map('intval', $ids);
        $this->selected = $on
            ? array_values(array_unique([...array_map('intval', $this->selected), ...$ids]))
            : array_values(array_diff(array_map('intval', $this->selected), $ids));
    }

    /**
     * Shift-click: tick or untick every row from one to another, in the page's order.
     *
     * @param  list<int|string>  $order
     */
    public function selectRange(int $from, int $to, bool $on, array $order): void
    {
        $this->selectRows($this->dataTableService()->range($order, $from, $to), $on);
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function selectPage(array $ids): void
    {
        $this->selectRows($ids);
    }

    public function selectAllMatching(): void
    {
        if (property_exists($this, 'selected')) {
            $this->selected = array_slice(array_map('intval', $this->dataTableMatchingIds()), 0, DataTable::MAX_SELECT_ALL);
        }
    }

    public function clearSelection(): void
    {
        $this->clearDataTableSelection();
    }

    public function startReordering(): void
    {
        if (! $this->dataTableReorderable() || ! $this->dataTableCanReorder()) {
            return;
        }

        $this->clearDataTableSelection();
        $this->reordering = true;
    }

    public function cancelReordering(): void
    {
        $this->reordering = false;
    }

    /**
     * Reorder mode's Save: the ids of this page's rows in their new order.
     *
     * @param  list<int|string>  $orderedIds
     */
    public function saveOrder(array $orderedIds): void
    {
        if (! $this->dataTableReorderable() || ! $this->dataTableCanReorder()) {
            $this->reordering = false;

            return;
        }

        $this->dataTableSaveOrder(array_values(array_map('intval', $orderedIds)));
        $this->reordering = false;
    }

    /**
     * Everything x-admin.data-table draws for this page of rows.
     *
     * @param  list<Row>  $rows
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function dataTable(?LengthAwarePaginator $page, array $rows, array $options = []): array
    {
        $reorderable = $this->dataTableReorderable() && ! $this->onTrash();

        return $this->dataTableService()->describe($this->dataTableColumns(), $this->columnChoices, $rows, $page, [
            'sort' => $this->sort,
            'direction' => $this->direction,
            'sortable' => $this->listSortColumns(),
            'density' => $this->density,
            'selectable' => property_exists($this, 'selected'),
            'selected' => property_exists($this, 'selected') ? $this->selected : [],
            'reordering' => $this->reordering && $reorderable,
            'reorderable' => $reorderable,
            'canReorder' => $reorderable && $this->dataTableCanReorder(),
            ...$options,
        ]);
    }

    protected function dataTableColumn(string $key): ?Column
    {
        foreach ($this->dataTableColumns() as $column) {
            if ($column->key === $key) {
                return $column;
            }
        }

        return null;
    }

    protected function sortsNewestFirst(Column $column): bool
    {
        return $column->type->descendsFirst();
    }

    private function clearDataTableSelection(): void
    {
        if (property_exists($this, 'selected')) {
            $this->selected = [];
        }
    }

    private function dataTableService(): DataTableService
    {
        return app(DataTableService::class);
    }
}
