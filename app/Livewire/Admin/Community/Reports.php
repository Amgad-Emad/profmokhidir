<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Community;

use App\Constants\AdminNavigation;
use App\Constants\ListToolbar;
use App\Http\Requests\Admin\CommunityReviewRequest;
use App\Livewire\Admin\Concerns\ConfirmsActions;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Livewire\Admin\Concerns\WithListToolbar;
use App\Models\ChatReport;
use App\Repositories\Contracts\ChatReportRepositoryInterface;
use App\Services\ChatService;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
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
    use AuthorizesRequests, ConfirmsActions, NotifiesEditors, WithListToolbar, WithPagination;

    public const string TAB_OPEN = ChatReportRepositoryInterface::STATE_OPEN;

    public const string TAB_RESOLVED = ChatReportRepositoryInterface::STATE_RESOLVED;

    public const string ACTION_DELETE = 'delete-message';

    public const string ACTION_SUSPEND = 'suspend-author';

    public const int PER_PAGE = 20;

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
        $reports = $this->service()->filterReports($this->search, $this->listCriteria(), self::PER_PAGE, $this->sort === 'created_at');

        return view('livewire.admin.community.reports', [
            'reports' => $reports,
            'toolbar' => $this->listToolbar($reports->total()),
            'state' => $this->filters[ChatReportRepositoryInterface::FILTER_STATE] ?? null,
            'filtered' => $this->listFiltered() && $this->activeView() === '',
        ])->layout('components.layouts.admin', ['title' => AdminNavigation::get(AdminNavigation::CHAT_REPORTS)['label']]);
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
