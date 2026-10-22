<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tributes;

use App\Constants\AdminNavigation;
use App\Constants\AdminUx;
use App\Constants\StatusTone;
use App\Constants\TributeStatus;
use App\Http\Requests\Admin\TributeEditRequest;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Models\Tribute;
use App\Services\TributeService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tribute moderation: Pending, Approved and Rejected tabs; approve, reject,
 * feature one approved tribute as the frontispiece of the book, fix typos (the
 * original stays in the audit log) and bulk actions. The visitor's email is
 * shown here, to moderators only, and nowhere else.
 */
class Moderate extends Component
{
    use AuthorizesRequests, NotifiesEditors, WithPagination;

    public const int PER_PAGE = 20;

    #[Url(except: 'pending')]
    public string $tab = 'pending';

    /** @var list<int> */
    public array $selected = [];

    public ?int $editingId = null;

    /** @var array{name?: string, location?: ?string, message?: string} */
    public array $edit = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Tribute::class);

        if (! TributeStatus::isValid($this->tab)) {
            $this->tab = TributeStatus::Pending->value;
        }
    }

    public function showTab(string $tab): void
    {
        if (TributeStatus::isValid($tab)) {
            $this->tab = $tab;
            $this->selected = [];
            $this->resetPage();
        }
    }

    public function approve(int $id): void
    {
        $tribute = $this->service()->findOrFail($id);
        $this->authorize('approve', $tribute);

        $this->service()->approve($tribute, auth()->user());
        $this->forget($id);
        $this->notify('Tribute from '.$tribute->name.' approved. It is on the Tributes page now.');
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
        $this->toast('Tribute from '.$tribute->name.' declined. It will not appear on the site.', AdminUx::TOAST_SUCCESS, $this->undoWith('restoreStatuses', [$previous]));
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
        $this->toast($count === 1 ? 'Decline undone.' : 'Declines undone for '.$count.' tributes.');
    }

    /**
     * Make an approved tribute the frontispiece of the book (one at a time).
     */
    public function feature(int $id): void
    {
        $tribute = $this->service()->findOrFail($id);
        $this->authorize('update', $tribute);

        if (! $tribute->isApproved()) {
            $this->notify('Only an approved tribute can be featured.');

            return;
        }

        $this->service()->feature($tribute, auth()->user());
        $this->notify('Tribute featured. It now opens the book of remembrance.');
    }

    public function unfeature(int $id): void
    {
        $tribute = $this->service()->findOrFail($id);
        $this->authorize('update', $tribute);

        $this->service()->unfeature($tribute, auth()->user());
        $this->notify('Tribute no longer featured.');
    }

    public function approveSelected(): void
    {
        $this->authorize('viewAny', Tribute::class);

        $count = $this->service()->approveMany($this->selected, auth()->user());
        $this->selected = [];
        $this->notify($count === 1 ? '1 tribute approved.' : $count.' tributes approved.');
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
        $this->toast($count === 1 ? '1 tribute declined.' : $count.' tributes declined.', AdminUx::TOAST_SUCCESS, $previous === [] ? null : $this->undoWith('restoreStatuses', [$previous]));
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
        $this->notify('Tribute updated. The original wording is kept in the audit log.');
    }

    public function render(): View
    {
        $tributes = $this->service()->paginateByStatus($this->tab, self::PER_PAGE);
        $tabs = [];

        foreach (TributeStatus::cases() as $status) {
            $tabs[$status->value] = ['label' => $status->label(), 'count' => $this->service()->countByStatus($status), 'tone' => StatusTone::forTribute($status)];
        }

        return view('livewire.admin.tributes.moderate', [
            'tributes' => $tributes,
            'tabs' => $tabs,
            'current' => TributeStatus::from($this->tab),
            'editing' => $this->editingId === null ? null : $this->service()->find($this->editingId),
            'pageIds' => $tributes->getCollection()->map(fn (Tribute $tribute): int => (int) $tribute->getKey())->all(),
        ])->layout('components.layouts.admin', ['title' => AdminNavigation::get(AdminNavigation::TRIBUTES)['label']]);
    }

    public function toggleAll(): void
    {
        $pageIds = $this->service()->paginateByStatus($this->tab, self::PER_PAGE)->getCollection()
            ->map(fn (Tribute $tribute): int => (int) $tribute->getKey())->all();

        $this->selected = array_diff($pageIds, $this->selected) === [] ? [] : $pageIds;
    }

    private function forget(int $id): void
    {
        $this->selected = array_values(array_filter($this->selected, fn ($selected): bool => (int) $selected !== $id));
    }

    private function service(): TributeService
    {
        return app(TributeService::class);
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
