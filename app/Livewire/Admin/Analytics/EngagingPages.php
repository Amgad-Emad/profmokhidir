<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Analytics;

use App\Constants\AdminNavigation;
use App\Constants\Analytics;
use App\Constants\DataTable;
use App\Constants\ListToolbar;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Livewire\Admin\Concerns\WithListToolbar;
use App\Models\AnalyticsDailyTotal;
use App\Services\AnalyticsPagesListService;
use App\Services\AnalyticsReportService;
use App\Services\LocaleService;
use App\Support\DataTable\Cell;
use App\Support\DataTable\Column;
use App\Support\DataTable\Row;
use App\Support\Duration;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The "Most engaging pages" table of /admin/analytics under the list toolbar:
 * a view per section read in the period, search by title or path, a Section
 * filter and sorting by engaged time, views, time per view or scroll depth.
 * The period comes from the report's own range form (it is not a list filter).
 */
class EngagingPages extends Component
{
    use AuthorizesRequests, WithListToolbar, WithPagination;
    use WithDataTable {
        WithDataTable::sortBy as sortByColumn;
    }

    /** The column of the default order (most engaged time first). */
    public const string COLUMN_ENGAGED = 'engaged';

    #[Locked]
    public string $from = '';

    #[Locked]
    public string $to = '';

    /** The report's range parameters, carried into the CSV link. @var array<string, string> */
    #[Locked]
    public array $exportQuery = [];

    /** @var list<array<string, mixed>>|null */
    private ?array $pageRows = null;

    /**
     * @param  array<string, string>  $exportQuery
     */
    public function mount(string $from, string $to, array $exportQuery = [], ?string $section = null): void
    {
        $this->authorize('viewAny', AnalyticsDailyTotal::class);

        $this->from = $from;
        $this->to = $to;
        $this->exportQuery = array_intersect_key($exportQuery, array_flip(['range', 'from', 'to']));

        // The report's ?section= (bookmarks and links from before the toolbar).
        if ($section !== null && $this->filters === []) {
            $this->filters = [AnalyticsPagesListService::FILTER_SECTION => $section];
        }
    }

    protected function listScreen(): string
    {
        return AdminNavigation::ANALYTICS;
    }

    protected function listFilters(): array
    {
        return [ListFilter::multi(AnalyticsPagesListService::FILTER_SECTION, __('Section'), Analytics::sections())];
    }

    /**
     * Every page, then one view per section read in the period.
     */
    protected function listViews(): array
    {
        $views = [new ListView(ListToolbar::VIEW_ALL, __('All pages'))];
        $read = $this->list()->sectionCounts($this->rows());

        foreach (Analytics::sections() as $section => $label) {
            if (isset($read[$section])) {
                $views[] = new ListView('section-'.$section, $label, [AnalyticsPagesListService::FILTER_SECTION => $section]);
            }
        }

        return $views;
    }

    protected function listSorts(): array
    {
        return [
            new ListSort('', ListToolbar::SORT_DESC, __('Engaged time')),
            new ListSort(AnalyticsPagesListService::SORT_VIEWS, ListToolbar::SORT_DESC, __('Views')),
            new ListSort(AnalyticsPagesListService::SORT_PER_VIEW, ListToolbar::SORT_DESC, __('Time per view')),
            new ListSort(AnalyticsPagesListService::SORT_SCROLL, ListToolbar::SORT_DESC, __('Scroll depth')),
        ];
    }

    protected function listCount(array $criteria, string $search): int
    {
        return $this->list()->count($this->rows(), $search, $criteria);
    }

    /**
     * The period is part of the address already; a remembered view would drop it.
     */
    protected function listRestores(): bool
    {
        return false;
    }

    public function render(): View
    {
        $rows = $this->list()->rows($this->rows(), $this->search, $this->listCriteria(), $this->sort, $this->direction);
        $sections = (array) ($this->listCriteria()[AnalyticsPagesListService::FILTER_SECTION] ?? []);
        $exportQuery = $this->exportQuery + (count($sections) === 1 ? ['section' => $sections[0]] : []);

        $page = new LengthAwarePaginator(array_slice($rows, ($this->getPage() - 1) * $this->perPage, $this->perPage), count($rows), $this->perPage, $this->getPage());

        return view('livewire.admin.analytics.engaging-pages', [
            'pages' => $page->items(),
            'table' => $this->dataTable($page, array_map(fn (array $row): Row => $this->row($row), $page->items()), [
                'caption' => __('Pages ranked by total engaged time'),
                'sort' => $this->sort === '' ? self::COLUMN_ENGAGED : $this->sort,
                'direction' => $this->sort === '' ? ListToolbar::SORT_DESC : $this->direction,
                'sortable' => [...$this->listSortColumns(), self::COLUMN_ENGAGED],
                'selectable' => false,
                'hasActions' => false,
                'id' => 'engaging-pages-table',
            ]),
            'toolbar' => $this->listToolbar(count($rows)),
            'sectionChosen' => $sections !== [],
            'exportUrl' => route(Analytics::EXPORT_ROUTE, ['export' => Analytics::EXPORT_PAGES, ...$exportQuery]),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    /**
     * The page with its section and address, then the figures, at the end edge.
     */
    protected function dataTableColumns(): array
    {
        return [
            Column::primary('label', __('Page')),
            Column::number(self::COLUMN_ENGAGED, __('Engaged time'), sortable: true, width: '8.5rem', priority: DataTable::PRIORITY_ESSENTIAL),
            Column::number(AnalyticsPagesListService::SORT_PER_VIEW, __('Per view'), sortable: true, width: '7rem'),
            Column::number(AnalyticsPagesListService::SORT_VIEWS, __('Views'), sortable: true, width: '6rem'),
            Column::number(AnalyticsPagesListService::SORT_SCROLL, __('Scroll depth'), sortable: true, width: '7.5rem', priority: DataTable::PRIORITY_DESKTOP),
        ];
    }

    /**
     * The engaged-time header goes back to the default order; the others sort by their figure.
     */
    public function sortBy(string $column): void
    {
        if ($column === self::COLUMN_ENGAGED) {
            $this->applySort('');

            return;
        }

        $this->sortByColumn($column);
    }

    /**
     * @param  array<string, mixed>  $page
     */
    private function row(array $page): Row
    {
        $digits = app(LocaleService::class);

        return new Row(
            id: crc32((string) $page['path']),
            title: (string) $page['label'],
            url: (string) $page['url'],
            supporting: [(string) $page['section_label'], (string) $page['path']],
            cells: [
                self::COLUMN_ENGAGED => new Cell(text: Duration::short((int) $page['engaged_seconds'])),
                AnalyticsPagesListService::SORT_PER_VIEW => new Cell(text: Duration::short((int) $page['average_engaged'])),
                AnalyticsPagesListService::SORT_VIEWS => new Cell(text: $digits->digits(number_format((int) $page['views']))),
                AnalyticsPagesListService::SORT_SCROLL => new Cell(text: $digits->digits(number_format((float) $page['average_scroll'], 0)).'%'),
            ],
            selectable: false,
            attributes: ['data-page' => (string) $page['path']],
        );
    }

    private function rows(): array
    {
        return $this->pageRows ??= app(AnalyticsReportService::class)->pageRows(CarbonImmutable::parse($this->from)->startOfDay(), CarbonImmutable::parse($this->to)->startOfDay());
    }

    private function list(): AnalyticsPagesListService
    {
        return app(AnalyticsPagesListService::class);
    }
}
