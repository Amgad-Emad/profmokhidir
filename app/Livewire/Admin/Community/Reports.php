<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Community;

use App\Constants\AdminNavigation;
use App\Http\Requests\Admin\CommunityReviewRequest;
use App\Livewire\Admin\Concerns\ConfirmsActions;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Models\ChatReport;
use App\Services\ChatService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Chat reports for moderators: open and resolved tabs. From a report an admin
 * can resolve it, delete the message or suspend its author (which also
 * resolves the report).
 */
class Reports extends Component
{
    use AuthorizesRequests, ConfirmsActions, NotifiesEditors, WithPagination;

    public const string TAB_OPEN = 'open';

    public const string TAB_RESOLVED = 'resolved';

    public const string ACTION_DELETE = 'delete-message';

    public const string ACTION_SUSPEND = 'suspend-author';

    public const int PER_PAGE = 20;

    #[Url(except: 'open')]
    public string $tab = self::TAB_OPEN;

    public string $note = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ChatReport::class);

        if (! in_array($this->tab, [self::TAB_OPEN, self::TAB_RESOLVED], true)) {
            $this->tab = self::TAB_OPEN;
        }
    }

    public function showTab(string $tab): void
    {
        if (in_array($tab, [self::TAB_OPEN, self::TAB_RESOLVED], true)) {
            $this->tab = $tab;
            $this->resetPage();
        }
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
        return view('livewire.admin.community.reports', [
            'reports' => $this->service()->paginateReports($this->tab === self::TAB_OPEN, self::PER_PAGE),
            'openCount' => $this->service()->countOpenReports(),
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
