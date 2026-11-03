<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tributes;

use App\Constants\AdminNavigation;
use App\Constants\AdminUx;
use App\Constants\DataTable;
use App\Constants\DataTableColumnType;
use App\Constants\ListToolbar;
use App\Constants\StatusTone;
use App\Constants\TributeRelationship;
use App\Constants\TributeStatus;
use App\Http\Requests\Admin\TributeEditRequest;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Livewire\Admin\Concerns\WithListToolbar;
use App\Models\Tribute;
use App\Services\DataTableService;
use App\Services\LocaleService;
use App\Services\TributeService;
use App\Support\DataTable\Cell;
use App\Support\DataTable\Column;
use App\Support\DataTable\Row;
use App\Support\DataTable\RowAction;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ViewErrorBag;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tribute moderation: the list toolbar (Pending, Approved, Rejected, Featured
 * and All views; search; Status, Relationship and Featured filters); approve, reject,
 * feature one approved tribute as the frontispiece of the book, fix typos (the
 * original stays in the audit log) and bulk actions. The visitor's email is
 * shown here, to moderators only, and nowhere else.
 */
class Moderate extends Component
{
    use AuthorizesRequests, NotifiesEditors, WithDataTable, WithListToolbar, WithPagination;

    public const int PER_PAGE = DataTable::DEFAULT_PAGE_SIZE;

    public const string VIEW_FEATURED = 'featured';

    /** @var list<int> */
    public array $selected = [];

    public ?int $editingId = null;

    /** @var array{name?: string, location?: ?string, message?: string} */
    public array $edit = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Tribute::class);
    }

    protected function listScreen(): string
    {
        return AdminNavigation::TRIBUTES;
    }

    protected function listFilters(): array
    {
        return [
            ListFilter::single('status', __('Status'), TributeStatus::labels()),
            ListFilter::multi('relationship', __('Relationship'), TributeRelationship::labels()),
            ListFilter::flag('is_featured', __('Featured'), __('Featured in the book'), __('Featured')),
        ];
    }

    /**
     * Pending first (the default), then each decision, the featured tribute and everything.
     */
    protected function listViews(): array
    {
        $views = [];

        foreach (TributeStatus::cases() as $status) {
            $views[] = new ListView($status->value, $status->label(), ['status' => $status->value]);
        }

        return [...$views, new ListView(self::VIEW_FEATURED, __('Featured'), ['is_featured' => ListToolbar::FLAG_ON]), new ListView(ListToolbar::VIEW_ALL, __('All'))];
    }

    protected function listDefaultView(): string
    {
        return TributeStatus::Pending->value;
    }

    protected function listSorts(): array
    {
        return [
            new ListSort('', ListToolbar::SORT_DESC, __('Newest first')),
            new ListSort('created_at', ListToolbar::SORT_ASC, __('Oldest first')),
            new ListSort('name', ListToolbar::SORT_ASC, __('Name, A to Z')),
        ];
    }

    protected function listCount(array $criteria, string $search): int
    {
        return $this->service()->count($search, $criteria);
    }

    protected function listGroupedCounts(string $key, array $criteria, string $search): ?array
    {
        return in_array($key, ['status', 'relationship'], true) ? $this->service()->countBy($key, $search, $criteria) : null;
    }

    /**
     * Name with relationship and place, the email (moderators only), when it
     * came in and its status; the words open under the row.
     */
    protected function dataTableColumns(): array
    {
        return [
            Column::primary('name', __('Name'), sortable: true),
            new Column('email', __('Email'), DataTableColumnType::Mono, width: '13rem', priority: DataTable::PRIORITY_DESKTOP),
            Column::relative('created_at', __('Received')),
            Column::status('status', __('Status')),
        ];
    }

    protected function dataTableMatchingIds(): array
    {
        return $this->service()->search($this->search, $this->listCriteria(), DataTable::MAX_SELECT_ALL)->getCollection()->modelKeys();
    }

    public function approve(int $id): void
    {
        $tribute = $this->service()->findOrFail($id);
        $this->authorize('approve', $tribute);

        $this->service()->approve($tribute, auth()->user());
        $this->forget($id);
        $this->notify(__('Tribute from :name approved. It is on the Tributes page now.', ['name' => $tribute->name]));
    }

    /**
     * Decline at once (it can be approved later, so there is no dialog); the
     * toast offers Undo for AdminUx::UNDO_SECONDS.
     */
    public function decline(int $id): void
    {
        $tribute = $this->service()->findOrFail($id);
        $this->authorize('reject', $tribute);

        $previous = [$id => $tribute->status->value];
        $this->service()->reject($tribute, auth()->user());
        $this->forget($id);
        $this->toast(__('Tribute from :name declined. It will not appear on the site.', ['name' => $tribute->name]), AdminUx::TOAST_SUCCESS, $this->undoWith('restoreStatuses', [$previous]));
    }

    /**
     * The Undo of a decline: each tribute goes back to the status it had.
     *
     * @param  array<int|string, string>  $statuses
     */
    public function restoreStatuses(array $statuses): void
    {
        $this->authorize('viewAny', Tribute::class);

        foreach (array_keys($statuses) as $id) {
            $this->authorize('approve', $this->service()->findOrFail((int) $id));
        }

        $count = $this->service()->restoreStatuses($statuses, auth()->user());
        $this->toast($count === 1 ? __('Decline undone.') : __('Declines undone for :count tributes.', ['count' => $this->digits($count)]));
    }

    /**
     * Make an approved tribute the frontispiece of the book (one at a time).
     */
    public function feature(int $id): void
    {
        $tribute = $this->service()->findOrFail($id);
        $this->authorize('update', $tribute);

        if (! $tribute->isApproved()) {
            $this->notify(__('Only an approved tribute can be featured.'));

            return;
        }

        $this->service()->feature($tribute, auth()->user());
        $this->notify(__('Tribute featured. It now opens the book of remembrance.'));
    }

    public function unfeature(int $id): void
    {
        $tribute = $this->service()->findOrFail($id);
        $this->authorize('update', $tribute);

        $this->service()->unfeature($tribute, auth()->user());
        $this->notify(__('Tribute no longer featured.'));
    }

    public function approveSelected(): void
    {
        $this->authorize('viewAny', Tribute::class);

        $count = $this->service()->approveMany($this->selected, auth()->user());
        $this->selected = [];
        $this->notify(trans_choice('{1} :count tribute approved.|[2,*] :count tributes approved.', $count, ['count' => $this->digits($count)]));
    }

    public function rejectSelected(): void
    {
        $this->authorize('viewAny', Tribute::class);

        $previous = [];

        foreach ($this->selected as $id) {
            $tribute = $this->service()->find((int) $id);

            if ($tribute !== null && $tribute->status !== TributeStatus::Rejected) {
                $previous[(int) $id] = $tribute->status->value;
            }
        }

        $count = $this->service()->rejectMany($this->selected, auth()->user());
        $this->selected = [];
        $this->toast(trans_choice('{1} :count tribute declined.|[2,*] :count tributes declined.', $count, ['count' => $this->digits($count)]), AdminUx::TOAST_SUCCESS, $previous === [] ? null : $this->undoWith('restoreStatuses', [$previous]));
    }

    public function startEditing(int $id): void
    {
        $tribute = $this->service()->findOrFail($id);
        $this->authorize('update', $tribute);

        $this->editingId = $id;
        $this->edit = ['name' => $tribute->name, 'location' => $tribute->location, 'message' => $tribute->message];
        $this->resetErrorBag();
    }

    public function cancelEditing(): void
    {
        $this->editingId = null;
        $this->edit = [];
        $this->resetErrorBag();
    }

    public function saveEdit(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $tribute = $this->service()->findOrFail($this->editingId);
        $this->authorize('update', $tribute);

        $request = new TributeEditRequest;
        $this->validate($this->prefix($request->rules()), $this->prefix($request->messages()), $this->prefix($request->attributes()));

        $this->service()->edit($tribute, $this->edit, auth()->user());
        $this->cancelEditing();
        $this->notify(__('Tribute updated. The original wording is kept in the audit log.'));
    }

    public function render(): View
    {
        $tributes = $this->tributes();
        $status = TributeStatus::tryFrom((string) ($this->filters['status'] ?? ''));

        return view('livewire.admin.tributes.moderate', [
            'tributes' => $tributes,
            'table' => $this->dataTable($tributes, $tributes->getCollection()->map(fn (Tribute $tribute): Row => $this->row($tribute))->all(), [
                'caption' => __('Tributes'),
                'id' => 'tributes-table',
            ]),
            'toolbar' => $this->listToolbar($tributes->total()),
            'current' => $status,
            'filtered' => $this->listFiltered() && $this->activeView() === '',
            'editing' => $this->editingId === null ? null : $this->service()->find($this->editingId),
            'pageIds' => $tributes->getCollection()->map(fn (Tribute $tribute): int => (int) $tribute->getKey())->all(),
        ])->layout('components.layouts.admin', ['title' => AdminNavigation::get(AdminNavigation::TRIBUTES)['label']]);
    }

    /**
     * One tribute as a table row: Approve and Decline at hand, Feature and Fix
     * typos in the menu, and the words (or the typo form) under the row.
     */
    private function row(Tribute $tribute): Row
    {
        $id = (int) $tribute->getKey();
        $editing = $this->editingId === $id;
        $tables = app(DataTableService::class);
        $approved = $tribute->isApproved();

        return new Row(
            id: $id,
            title: $tribute->name,
            supporting: [$tribute->relationship->label(), $tribute->location],
            badges: $tribute->isFeatured() ? [['label' => __('Featured'), 'tone' => StatusTone::Cypress, 'hook' => 'data-featured-badge']] : [],
            cells: [
                'email' => new Cell(text: $tribute->email ?: __('No email given'), muted: ! $tribute->email),
                'created_at' => $tables->relative($tribute->created_at),
                'status' => Cell::status($tribute->status->label(), StatusTone::forTribute($tribute->status)),
            ],
            actions: array_values(array_filter([
                ! $approved ? RowAction::wire(__('Approve'), 'check', 'approve', [$id], inline: true, hook: 'data-approve') : null,
                $tribute->status !== TributeStatus::Rejected ? RowAction::wire(__('Decline'), 'close', 'decline', [$id], inline: true, danger: true, hook: 'data-reject') : null,
                $approved && $tribute->isFeatured() ? RowAction::wire(__('Remove from featured'), 'minus', 'unfeature', [$id], hook: 'data-unfeature') : null,
                $approved && ! $tribute->isFeatured() ? RowAction::wire(__('Feature on the page'), 'check', 'feature', [$id], hook: 'data-feature') : null,
                ! $editing ? RowAction::wire(__('Fix typos'), 'feather', 'startEditing', [$id], hook: 'data-edit') : null,
            ])),
            detail: new HtmlString($this->detail($tribute, $editing)),
            open: $editing,
            detailLabel: __('Read the tribute'),
            attributes: ['data-tribute' => $id],
        );
    }

    /**
     * The words under a row, rendered now (anonymous form components read the errors shared with every view).
     */
    private function detail(Tribute $tribute, bool $editing): string
    {
        if ($editing) {
            view()->share('errors', (new ViewErrorBag)->put('default', $this->getErrorBag()));
        }

        return view('livewire.admin.tributes.partials.detail', ['tribute' => $tribute, 'editing' => $editing])->render();
    }

    public function toggleAll(): void
    {
        $pageIds = $this->tributes()->getCollection()
            ->map(fn (Tribute $tribute): int => (int) $tribute->getKey())->all();

        $this->selected = array_diff($pageIds, $this->selected) === [] ? [] : $pageIds;
    }

    /**
     * The page of tributes for the search, filters and sort (newest first unless sorted).
     *
     * @return LengthAwarePaginator<int, Tribute>
     */
    private function tributes(): LengthAwarePaginator
    {
        return $this->service()->search($this->search, $this->listCriteria(), $this->perPage, $this->sort ?: 'created_at', $this->sort === '' ? ListToolbar::SORT_DESC : $this->direction);
    }

    private function forget(int $id): void
    {
        $this->selected = array_values(array_filter($this->selected, fn ($selected): bool => (int) $selected !== $id));
    }

    private function service(): TributeService
    {
        return app(TributeService::class);
    }

    private function digits(int $count): string
    {
        return app(LocaleService::class)->digits($count);
    }

    private function notify(string $message): void
    {
        $this->toast($message);
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array<string, mixed>
     */
    private function prefix(array $items): array
    {
        $prefixed = [];

        foreach ($items as $key => $value) {
            $prefixed['edit.'.$key] = $value;
        }

        return $prefixed;
    }
}
