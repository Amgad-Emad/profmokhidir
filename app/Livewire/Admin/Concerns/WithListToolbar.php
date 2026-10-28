<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Concerns;

use App\Constants\ListDensity;
use App\Constants\ListToolbar;
use App\Services\ListToolbarService;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use Livewire\Attributes\Url;

/**
 * The state and actions behind x-admin.list-toolbar on a Livewire list
 * screen (which also uses WithPagination). Search, filters, sort, tab and page
 * live in the address; the view the user leaves a screen in is remembered and
 * comes back when they open it again without a query string.
 *
 * A screen declares its filters (ListFilter), views (ListView, the tabs) and
 * sorts (ListSort), counts its records for a set of criteria, and passes
 * listToolbar($shown) to the component. `$tab` is '' while the screen is in
 * its default view (or in a combination no view matches).
 */
trait WithListToolbar
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var array<string, string> */
    #[Url(except: [])]
    public array $filters = [];

    #[Url(except: '')]
    public string $sort = '';

    #[Url(except: 'asc')]
    public string $direction = ListToolbar::SORT_ASC;

    #[Url(except: '')]
    public string $tab = '';

    public string $density = ListDensity::Comfortable->value;

    /** @var array<string, int>|null Counts memoised within one request. */
    private ?array $listCountMemo = null;

    /** AdminNavigation key of the screen (the remembered view is kept under it). */
    abstract protected function listScreen(): string;

    /**
     * @return list<ListFilter>
     */
    abstract protected function listFilters(): array;

    /**
     * @return list<ListView>
     */
    abstract protected function listViews(): array;

    /**
     * How many records match the criteria (filters with multi-choice values as lists) and the search.
     *
     * @param  array<string, string|list<string>>  $criteria
     */
    abstract protected function listCount(array $criteria, string $search): int;

    /**
     * @return list<ListSort>
     */
    protected function listSorts(): array
    {
        return [];
    }

    /**
     * Columns the screen accepts as sort keys (the Sort menu's, unless widened).
     *
     * @return list<string>
     */
    protected function listSortColumns(): array
    {
        return array_values(array_filter(array_map(fn (ListSort $sort): string => $sort->column, $this->listSorts())));
    }

    protected function listDefaultView(): string
    {
        return ListToolbar::VIEW_ALL;
    }

    /**
     * Counts of every option of one filter in one query, or null to count option by option.
     *
     * @param  array<string, string|list<string>>  $criteria
     * @return array<string, int>|null
     */
    protected function listGroupedCounts(string $key, array $criteria, string $search): ?array
    {
        return null;
    }

    /**
     * Whether opening the screen without a query string brings back the user's last view.
     */
    protected function listRestores(): bool
    {
        return true;
    }

    protected function listViewCount(ListView $view): int
    {
        return $this->memoCount($this->toolbarService()->criteria($this->toolbarService()->viewFilters($view, $this->listFilters()), $this->listFilters()), '');
    }

    public function mountWithListToolbar(): void
    {
        $service = $this->toolbarService();
        $definitions = $this->listFilters();
        $views = $this->listViews();

        $this->search = mb_substr($this->search, 0, ListToolbar::MAX_SEARCH_LENGTH);
        $this->filters = $service->normalise($this->filters, $definitions);

        if ($this->sort !== '' && ! in_array($this->sort, $this->listSortColumns(), true)) {
            $this->sort = '';
        }

        if (! in_array($this->direction, ListToolbar::directions(), true)) {
            $this->direction = ListToolbar::SORT_ASC;
        }

        $view = $service->view($views, $this->tab);

        if ($view === null) {
            $this->tab = '';
        }

        $fresh = $this->tab === '' && $this->filters === [] && $this->search === '' && $this->sort === '';
        $user = auth()->user();

        if ($fresh && $user !== null && $this->listRestores() && request()->route() !== null && request()->query() === []) {
            $remembered = $service->recall($user, $this->listScreen());

            if ($remembered !== null) {
                $this->redirect(request()->url().'?'.http_build_query($remembered));

                return;
            }
        }

        if ($fresh) {
            $this->filters = $service->initialFilters($service->view($views, $this->listDefaultView()), $definitions);
        } elseif ($view !== null && ! $view->trash && $this->filters === []) {
            // A bookmarked ?tab= without its filters: the view sets them.
            $this->filters = $service->viewFilters($view, $definitions);
        }

        if ($view === null || ! $view->trash) {
            $this->tab = $this->tabFor($this->tab);
        }

        $this->density = $user === null ? ListDensity::DEFAULT->value : $service->density($user, $this->listScreen())->value;
    }

    public function updatedWithListToolbar(string $path): void
    {
        if ($path === 'search') {
            $this->search = mb_substr($this->search, 0, ListToolbar::MAX_SEARCH_LENGTH);
            $this->listChanged();
        } elseif ($path === 'filters' || str_starts_with($path, 'filters.')) {
            $this->filters = $this->toolbarService()->normalise($this->filters, $this->listFilters());
            $this->tab = $this->tabFor('');
            $this->listChanged();
        }
    }

    /**
     * A tab: set exactly the view's filters (or open the trash).
     */
    public function applyView(string $key): void
    {
        $view = $this->toolbarService()->view($this->listViews(), $key);

        if ($view === null) {
            return;
        }

        if (method_exists($this, 'cancelConfirmation')) {
            $this->cancelConfirmation();
        }

        $this->filters = $view->trash ? [] : $this->toolbarService()->viewFilters($view, $this->listFilters());
        $this->tab = $view->trash || $key !== $this->listDefaultView() ? $key : '';
        $this->listChanged();
    }

    /**
     * The tabs of the screens that had them before the toolbar (status tabs).
     */
    public function showTab(string $key): void
    {
        $this->applyView($key);
    }

    /**
     * Set one filter to a value, or clear it with null.
     */
    public function setFilter(string $key, ?string $value = null): void
    {
        $definition = $this->listFilter($key);

        if ($definition === null) {
            return;
        }

        $filters = $this->toolbarService()->remove($this->filters, $definition);
        $this->applyFilterState($value === null || $value === '' ? $filters : $this->toolbarService()->toggle($filters, $definition, $value));
    }

    /**
     * An option of a filter's popover: a radio choice, a checkbox or a flag turned on or off.
     */
    public function toggleFilter(string $key, string $value): void
    {
        $definition = $this->listFilter($key);

        if ($definition !== null) {
            $this->applyFilterState($this->toolbarService()->toggle($this->filters, $definition, $value));
        }
    }

    /**
     * A chip's ✕: take one value away (or the whole filter with null).
     */
    public function removeFilter(string $key, ?string $value = null): void
    {
        $definition = $this->listFilter($key);

        if ($definition !== null) {
            $this->applyFilterState($this->toolbarService()->remove($this->filters, $definition, $value));
        }
    }

    /**
     * The filters sheet's Apply: every filter, the sort and the density at once.
     *
     * @param  array<mixed>  $filters
     */
    public function applyFilters(array $filters, ?string $sort = null, ?string $density = null): void
    {
        $joined = [];

        foreach ($filters as $key => $values) {
            $joined[$key] = is_array($values) ? implode(ListToolbar::VALUE_SEPARATOR, array_filter($values, 'is_scalar')) : $values;
        }

        if ($sort !== null) {
            [$column, $direction] = array_pad(explode(':', $sort, 2), 2, ListToolbar::SORT_ASC);
            $this->applySortState($column, $direction);
        }

        if ($density !== null) {
            $this->setDensity($density);
        }

        $this->applyFilterState($this->toolbarService()->normalise($joined, $this->listFilters()));
    }

    public function clearFilters(): void
    {
        $this->search = '';

        if ($this->onTrash()) {
            $this->listChanged();

            return;
        }

        $this->applyFilterState([]);
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->listChanged();
    }

    /**
     * A choice of the Sort menu ('' column: the screen's own order).
     */
    public function applySort(string $column, string $direction = ListToolbar::SORT_ASC): void
    {
        $this->applySortState($column, $direction);
        $this->listChanged();
    }

    public function toggleDensity(): void
    {
        $this->setDensity(ListDensity::from($this->density)->other()->value);
    }

    public function setDensity(string $density): void
    {
        $value = ListDensity::tryFrom($density);

        if ($value === null) {
            return;
        }

        $this->density = $value->value;

        if (auth()->user() !== null) {
            $this->toolbarService()->setDensity(auth()->user(), $this->listScreen(), $value);
        }
    }

    /**
     * The key of the tab shown as selected ('' when no view matches).
     */
    public function activeView(): string
    {
        if ($this->onTrash()) {
            return $this->tab;
        }

        $tab = $this->tab === '' ? $this->listDefaultView() : $this->tab;

        return $this->toolbarService()->matchingView($this->filters, $this->listViews(), $this->listFilters(), $tab);
    }

    public function onTrash(): bool
    {
        return $this->toolbarService()->view($this->listViews(), $this->tab)?->trash ?? false;
    }

    /**
     * The filters as the repositories take them.
     *
     * @return array<string, string|list<string>>
     */
    public function listCriteria(): array
    {
        return $this->toolbarService()->criteria($this->filters, $this->listFilters());
    }

    public function listFiltered(): bool
    {
        return trim($this->search) !== '' || $this->filters !== [];
    }

    /**
     * Everything x-admin.list-toolbar draws; `shown` is the number of records found.
     *
     * @return array<string, mixed>
     */
    public function listToolbar(int $shown): array
    {
        return $this->toolbarService()->describe(
            $this->listFilters(),
            $this->listViews(),
            $this->listSorts(),
            [
                'search' => $this->search,
                'filters' => $this->filters,
                'sort' => $this->sort,
                'direction' => $this->direction,
                'tab' => $this->activeView(),
                'density' => ListDensity::tryFrom($this->density) ?? ListDensity::DEFAULT,
                'shown' => $shown,
            ],
            fn (array $criteria, string $search): int => $this->memoCount($criteria, $search),
            fn (ListView $view): int => $this->listViewCount($view),
            fn (string $key, array $criteria, string $search): ?array => $this->listGroupedCounts($key, $criteria, $search),
        );
    }

    /**
     * @param  array<string, string>  $filters
     */
    protected function applyFilterState(array $filters): void
    {
        $this->filters = $filters;
        $this->tab = $this->tabFor('');
        $this->listChanged();
    }

    protected function applySortState(string $column, string $direction): void
    {
        if ($column !== '' && ! in_array($column, $this->listSortColumns(), true)) {
            return;
        }

        $this->sort = $column;
        $this->direction = in_array($direction, ListToolbar::directions(), true) && $column !== '' ? $direction : ListToolbar::SORT_ASC;
    }

    /**
     * After any change: back to page 1, the selection cleared, the view
     * remembered, and the browser told so it can keep the band in view.
     */
    protected function listChanged(): void
    {
        if (property_exists($this, 'selected')) {
            $this->selected = [];
        }

        $this->resetPage();
        $this->listCountMemo = null;

        if (auth()->user() !== null) {
            $this->toolbarService()->remember(auth()->user(), $this->listScreen(), [
                ListToolbar::PARAM_SEARCH => $this->search,
                ListToolbar::PARAM_FILTERS => $this->filters,
                ListToolbar::PARAM_SORT => $this->sort,
                ListToolbar::PARAM_DIRECTION => $this->direction,
                ListToolbar::PARAM_TAB => $this->onTrash() ? '' : $this->tab,
            ]);
        }

        $this->dispatch(ListToolbar::EVENT_FILTERED);
    }

    /**
     * The tab to keep for the current filters: '' for the default view,
     * otherwise the matching view (preferring `preferred`), or '' for none.
     */
    private function tabFor(string $preferred): string
    {
        $match = $this->toolbarService()->matchingView($this->filters, $this->listViews(), $this->listFilters(), $preferred === '' ? $this->listDefaultView() : $preferred);

        return $match === $this->listDefaultView() ? '' : $match;
    }

    private function listFilter(string $key): ?ListFilter
    {
        foreach ($this->listFilters() as $definition) {
            if ($definition->key === $key) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string|list<string>>  $criteria
     */
    private function memoCount(array $criteria, string $search): int
    {
        $key = md5(serialize([$criteria, $search]));

        return $this->listCountMemo[$key] ??= $this->listCount($criteria, $search);
    }

    private function toolbarService(): ListToolbarService
    {
        return app(ListToolbarService::class);
    }
}
