<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Community;

use App\Constants\AdminNavigation;
use App\Constants\DataTable;
use App\Constants\ListToolbar;
use App\Constants\StatusTone;
use App\Http\Requests\Admin\CommunityReviewRequest;
use App\Livewire\Admin\Concerns\ConfirmsActions;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Livewire\Admin\Concerns\WithListToolbar;
use App\Models\ChatReport;
use App\Repositories\Contracts\ChatReportRepositoryInterface;
use App\Services\ChatService;
use App\Services\DataTableService;
use App\Support\DataTable\Cell;
use App\Support\DataTable\Column;
use App\Support\DataTable\Row;
use App\Support\DataTable\RowAction;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Chat reports for moderators under the list toolbar: Open, Resolved and All
 * views; search; State, Channel and Message filters. From a report an admin
 * can resolve it, delete the message or suspend its author (which also
 * resolves the report).
 */
class Reports extends Component
{
    use AuthorizesRequests, ConfirmsActions, NotifiesEditors, WithDataTable, WithListToolbar, WithPagination;

    public const string TAB_OPEN = ChatReportRepositoryInterface::STATE_OPEN;

    public const string TAB_RESOLVED = ChatReportRepositoryInterface::STATE_RESOLVED;

    public const string ACTION_DELETE = 'delete-message';

    public const string ACTION_SUSPEND = 'suspend-author';

    public const int PER_PAGE = DataTable::DEFAULT_PAGE_SIZE;

    public string $note = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ChatReport::class);
    }

    protected function listScreen(): string
    {
        return AdminNavigation::CHAT_REPORTS;
    }

    protected function listFilters(): array
    {
        return [
            ListFilter::single(ChatReportRepositoryInterface::FILTER_STATE, __('State'), [self::TAB_OPEN => __('list-toolbar.reports_open'), self::TAB_RESOLVED => __('Resolved')]),
            ListFilter::multi(ChatReportRepositoryInterface::FILTER_CHANNEL, __('Channel'), $this->service()->channels()->pluck('name', 'id')->all()),
            ListFilter::single(ChatReportRepositoryInterface::FILTER_MESSAGE, __('Message'), [ChatReportRepositoryInterface::MESSAGE_VISIBLE => __('Still visible'), ChatReportRepositoryInterface::MESSAGE_REMOVED => __('Removed')]),
        ];
    }

    protected function listViews(): array
    {
        return [
            new ListView(self::TAB_OPEN, __('list-toolbar.reports_open'), [ChatReportRepositoryInterface::FILTER_STATE => self::TAB_OPEN]),
            new ListView(self::TAB_RESOLVED, __('Resolved'), [ChatReportRepositoryInterface::FILTER_STATE => self::TAB_RESOLVED]),
            new ListView(ListToolbar::VIEW_ALL, __('All')),
        ];
    }

    protected function listDefaultView(): string
    {
        return self::TAB_OPEN;
    }

    protected function listSorts(): array
    {
        return [
            new ListSort('', ListToolbar::SORT_DESC, __('Newest first')),
            new ListSort('created_at', ListToolbar::SORT_ASC, __('Oldest first')),
        ];
    }

    protected function listCount(array $criteria, string $search): int
    {
        return $this->service()->countReports($search, $criteria);
    }

    public function resolve(int $id): void
    {
        $report = $this->report($id);
        $this->authorize('resolve', $report);

        $this->service()->resolveReport($report, auth()->user());
        $this->toast(__('Report resolved.'));
    }

    public function deleteMessage(): void
    {
        if (! $this->isConfirming(self::ACTION_DELETE)) {
            return;
        }

        $report = $this->report((int) $this->confirmingId);
        $this->authorize('resolve', $report);

        $this->service()->deleteReportedMessage($report, auth()->user());
        $this->cancelConfirmation();
        $this->toast(__('Message removed and report resolved.'));
    }

    public function suspendAuthor(): void
    {
        if (! $this->isConfirming(self::ACTION_SUSPEND)) {
            return;
        }

        $report = $this->report((int) $this->confirmingId);
        $this->authorize('resolve', $report);

        $request = new CommunityReviewRequest;
        $validated = Validator::make(['note' => $this->note], $request->rules(), $request->messages())->validate();

        $this->service()->suspendReportedAuthor($report, auth()->user(), filled($validated['note'] ?? null) ? trim((string) $validated['note']) : null);
        $this->cancelConfirmation();
        $this->note = '';
        $this->toast(__('Author suspended and report resolved.'));
    }

    public function render(): View
    {
        // Newest first unless sorted by date ascending (the Sort menu's "Oldest first" or the header).
        $reports = $this->service()->filterReports($this->search, $this->listCriteria(), $this->perPage, $this->sort === 'created_at' && $this->direction === ListToolbar::SORT_ASC);

        return view('livewire.admin.community.reports', [
            'reports' => $reports,
            'table' => $this->dataTable($reports, $reports->getCollection()->map(fn (ChatReport $report): Row => $this->row($report))->all(), [
                'caption' => __('Chat reports'),
                'id' => 'reports-table',
            ]),
            'toolbar' => $this->listToolbar($reports->total()),
            'state' => $this->filters[ChatReportRepositoryInterface::FILTER_STATE] ?? null,
            'filtered' => $this->listFiltered() && $this->activeView() === '',
        ])->layout('components.layouts.admin', ['title' => AdminNavigation::get(AdminNavigation::CHAT_REPORTS)['label']]);
    }

    /**
     * The reason with who reported it and where, the message's author, when it
     * was reported and whether it is resolved; the message opens under the row.
     */
    protected function dataTableColumns(): array
    {
        return [
            Column::primary('reason', __('Reason')),
            Column::text('author', __('Message by'), width: '10rem'),
            Column::relative('created_at', __('Reported')),
            Column::status('state', __('Status')),
        ];
    }

    protected function sortsNewestFirst(Column $column): bool
    {
        return false;
    }

    /**
     * One report as a row: Resolve and Remove message at hand, Suspend author in the menu.
     */
    private function row(ChatReport $report): Row
    {
        $id = (int) $report->getKey();
        $message = $report->message;
        $open = ! $report->isResolved();
        $may = auth()->user()?->can('resolve', $report) ?? false;

        return new Row(
            id: $id,
            title: $report->reason ?: __('No reason given.'),
            supporting: [__('Reported by :name', ['name' => $report->reporter?->name ?? __('a former member')]), $message?->channel ? __('in :channel', ['channel' => $message->channel->name]) : null],
            cells: [
                'author' => Cell::text($message?->user?->name ?? __('a former member')),
                'created_at' => app(DataTableService::class)->relative($report->created_at),
                'state' => Cell::status($open ? __('list-toolbar.reports_open') : __('Resolved'), $open ? StatusTone::Bronze : StatusTone::Cypress),
            ],
            actions: $open && $may ? array_values(array_filter([
                RowAction::wire(__('Resolve'), 'check', 'resolve', [$id], inline: true, hook: 'data-resolve'),
                $message !== null && ! $message->trashed() ? RowAction::wire(__('Remove message'), 'trash', 'confirm', [self::ACTION_DELETE, $id], inline: true, danger: true, hook: 'data-delete-message') : null,
                $message?->user !== null && $message->user->community_status->canChat() ? RowAction::wire(__('Suspend author'), 'lock', 'confirm', [self::ACTION_SUSPEND, $id], danger: true, hook: 'data-suspend-author') : null,
            ])) : [],
            detail: new HtmlString(view('livewire.admin.community.partials.report', ['report' => $report, 'message' => $message])->render()),
            detailLabel: __('Show the message'),
            attributes: ['data-report' => $id],
        );
    }

    private function report(int $id): ChatReport
    {
        $report = $this->service()->findReport($id);

        if ($report === null) {
            abort(404);
        }

        return $report;
    }

    private function service(): ChatService
    {
        return app(ChatService::class);
    }
}
