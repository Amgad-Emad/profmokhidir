<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Pages;

use App\Constants\AdminNavigation;
use App\Constants\ContentStatus;
use App\Constants\ListToolbar;
use App\Livewire\Admin\Concerns\WithListToolbar;
use App\Models\Page;
use App\Services\PageListService;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
    use AuthorizesRequests, WithListToolbar, WithPagination;

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

        return view('livewire.admin.pages.index', [
            'rows' => $rows,
            'toolbar' => $this->listToolbar($rows->count()),
        ])->layout('components.layouts.admin', ['title' => __('Pages'), 'crumb' => __('Pages')]);
    }

    /** One list per request, so the pages are read once for the rows and every count. */
    private ?PageListService $pageList = null;

    private function list(): PageListService
    {
        return $this->pageList ??= app(PageListService::class);
    }
}
