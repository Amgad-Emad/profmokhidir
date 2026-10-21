<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Tributes;

use App\Constants\AdminNavigation;
use App\Constants\StatusTone;
use App\Constants\TributeStatus;
use App\Http\Requests\Admin\TributeEditRequest;
use App\Livewire\Admin\Concerns\ConfirmsActions;
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
    use AuthorizesRequests, ConfirmsActions, WithPagination;

    public const string ACTION_REJECT = 'reject';

    public const string ACTION_BULK_REJECT = 'bulk-reject';

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
        $this->notify('Tribute approved and published.');
    }

    public function reject(): void
    {
        if (! $this->isConfirming(self::ACTION_REJECT)) {
            return;
        }

        $tribute = $this->service()->findOrFail((int) $this->confirmingId);
        $this->authorize('reject', $tribute);

        $this->service()->reject($tribute, auth()->user());
        $this->forget((int) $tribute->getKey());
        $this->cancelConfirmation();
        $this->notify('Tribute rejected.');
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

    public function confirmRejectSelected(): void
    {
        if ($this->selected !== []) {
            $this->confirm(self::ACTION_BULK_REJECT, count($this->selected));
        }
    }

    public function rejectSelected(): void
    {
        if (! $this->isConfirming(self::ACTION_BULK_REJECT)) {
            return;
        }

        $this->authorize('viewAny', Tribute::class);

        $count = $this->service()->rejectMany($this->selected, auth()->user());
        $this->selected = [];
        $this->cancelConfirmation();
        $this->notify($count === 1 ? '1 tribute rejected.' : $count.' tributes rejected.');
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
        session()->flash('admin.status', $message);
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
