<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Pages;

use App\Constants\AdminNavigation;
use App\Constants\ContentStatus;
use App\Constants\ListToolbar;
use App\Constants\StatusTone;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Livewire\Admin\Concerns\WithListToolbar;
use App\Models\Page;
use App\Services\DataTableService;
use App\Services\PageListService;
use App\Support\DataTable\Cell;
use App\Support\DataTable\Column;
use App\Support\DataTable\Row;
use App\Support\DataTable\RowAction;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin › Pages: every public page whose words and images editors change,
 * in site order, with its status, whether it has unpublished changes and when
 * it was last saved, under the list toolbar (views, search, Status, Changes
 * and Language filters, sort). Pages are fixed by the design: none is added or removed.
 */
class Index extends Component
{
    use AuthorizesRequests, WithDataTable, WithListToolbar, WithPagination;

    public const string VIEW_CHANGES = 'changes';

    public const string VIEW_NEEDS_ARABIC = 'needs-arabic';

    public const string VIEW_DRAFTS = 'drafts';

    public function mount(): void
    {
        $this->authorize('viewAny', Page::class);
    }

    protected function listScreen(): string
    {
        return AdminNavigation::PAGES;
    }

    protected function listFilters(): array
    {
        return [
            ListFilter::single(PageListService::FILTER_STATUS, __('Status'), ContentStatus::labels()),
            ListFilter::flag(PageListService::FILTER_CHANGES, __('Changes'), __('Unpublished changes'), __('Unpublished changes')),
            ListFilter::flag(PageListService::FILTER_NEEDS_ARABIC, __('Language'), __('Needs Arabic translation'), __('Needs Arabic')),
        ];
    }

    protected function listViews(): array
    {
        return [
            new ListView(ListToolbar::VIEW_ALL, __('All')),
            new ListView(self::VIEW_CHANGES, __('Unpublished changes'), [PageListService::FILTER_CHANGES => ListToolbar::FLAG_ON]),
            new ListView(self::VIEW_NEEDS_ARABIC, __('Needs Arabic'), [PageListService::FILTER_NEEDS_ARABIC => ListToolbar::FLAG_ON]),
            new ListView(self::VIEW_DRAFTS, __('Drafts'), [PageListService::FILTER_STATUS => ContentStatus::Draft->value]),
        ];
    }

    protected function listSorts(): array
    {
        return [
            new ListSort('', ListToolbar::SORT_ASC, __('Site order')),
            new ListSort(PageListService::SORT_TITLE, ListToolbar::SORT_ASC, __('Title, A to Z')),
            new ListSort(PageListService::SORT_SAVED, ListToolbar::SORT_DESC, __('Recently saved')),
        ];
    }

    protected function listCount(array $criteria, string $search): int
    {
        return $this->list()->count($search, $criteria);
    }

    public function render(): View
    {
        $rows = $this->list()->rows($this->search, $this->listCriteria(), $this->sort, $this->direction);
        $page = new LengthAwarePaginator($rows->forPage($this->getPage(), $this->perPage)->values(), $rows->count(), $this->perPage, $this->getPage());

        return view('livewire.admin.pages.index', [
            'rows' => $rows,
            'table' => $this->dataTable($page, $page->getCollection()->map(fn (array $row): Row => $this->row($row))->all(), [
                'caption' => __('Pages'),
                'id' => 'pages-table',
            ]),
            'toolbar' => $this->listToolbar($rows->count()),
        ])->layout('components.layouts.admin', ['title' => __('Pages'), 'crumb' => __('Pages')]);
    }

    /**
     * The page with its purpose, its status (badges for unpublished changes and
     * missing Arabic) and when it was last saved.
     */
    protected function dataTableColumns(): array
    {
        return [
            Column::primary(PageListService::SORT_TITLE, __('Page'), sortable: true),
            Column::status('status', __('Status')),
            Column::relative(PageListService::SORT_SAVED, __('Last saved')),
        ];
    }

    /**
     * @param  array{page: Page, definition: array<string, mixed>, title: string, summary: string, hasDraft: bool, needsArabic: bool, editUrl: string}  $row
     */
    private function row(array $row): Row
    {
        $page = $row['page'];
        $route = $row['definition']['route'] ?? null;
        $published = $page->status === ContentStatus::Published;
        $editUrl = auth()->user()?->can('update', $page) ? $row['editUrl'] : null;

        return new Row(
            id: (int) $page->getKey(),
            title: $row['title'],
            url: $editUrl,
            supporting: [$row['summary']],
            badges: array_values(array_filter([
                $row['hasDraft'] ? ['label' => __('Unpublished changes'), 'tone' => StatusTone::Bronze, 'hook' => 'data-has-draft'] : null,
                $row['needsArabic'] ? ['label' => __('Needs Arabic'), 'tone' => StatusTone::Bronze, 'hook' => 'data-needs-arabic'] : null,
            ])),
            cells: [
                'status' => Cell::status($page->status->label(), StatusTone::forContent($page->status)),
                PageListService::SORT_SAVED => app(DataTableService::class)->relative($page->updated_at),
            ],
            actions: array_values(array_filter([
                $editUrl ? RowAction::link(__('Edit'), 'pencil', $editUrl, inline: true, hook: 'data-edit') : null,
                $published && $route !== null && Route::has($route) ? RowAction::link(__('View on site'), 'external', route($route), inline: true, newTab: true, hook: 'data-view-on-site') : null,
            ])),
            attributes: ['data-page-row' => $page->key],
        );
    }

    /** One list per request, so the pages are read once for the rows and every count. */
    private ?PageListService $pageList = null;

    private function list(): PageListService
    {
        return $this->pageList ??= app(PageListService::class);
    }
}
