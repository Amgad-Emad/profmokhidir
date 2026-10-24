{{--
    Modal confirmation. Shown while `show` is true; Escape or Cancel calls `cancel`,
    the confirm button calls `action` (both Livewire method names). Focus is trapped and
    starts on Cancel, so Enter never confirms by accident. `danger` is for irreversible
    actions: the danger icon beside the title, the consequence in the body (the slot), and
    the filled danger button (App\Constants\ButtonVariant::DangerFilled) as the confirm. A
    reversible action (move to trash) never opens a dialog: it offers Undo in the toast.
--}}
@props(['show' => false, 'title', 'action', 'cancel' => 'cancelConfirmation', 'confirmLabel' => 'Yes, continue', 'cancelLabel' => \App\Constants\ConfirmDialog::CANCEL, 'danger' => false, 'icon' => \App\Constants\ConfirmDialog::DELETE_ICON])
@if ($show)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.{{ $cancel }}()" data-confirm-dialog>
        <div role="{{ $danger ? 'alertdialog' : 'dialog' }}" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-description" @class(['confirm-dialog', 'confirm-dialog--danger' => $danger]) {{ $attributes }}>
            <div class="confirm-dialog__head">
                @if ($danger)
                    <span class="confirm-dialog__icon" data-danger-icon><x-icon name="warning" class="size-6" /></span>
                @endif
                <h2 id="confirm-title" class="confirm-dialog__title">{{ $title }}</h2>
            </div>
            <div id="confirm-description" class="confirm-dialog__body">{{ $slot }}</div>
            <div class="confirm-dialog__actions">
                <x-button type="button" variant="quiet" wire:click="{{ $cancel }}" x-init="$nextTick(() => $el.focus())" data-cancel>{{ $cancelLabel }}</x-button>
                <x-button type="button" :variant="$danger ? 'danger-filled' : 'primary'" wire:click="{{ $action }}" wire:loading.attr="disabled" data-confirm>@if ($danger && $icon)<x-icon :name="$icon" class="size-4" />@endif{{ $confirmLabel }}</x-button>
            </div>
        </div>
    </div>
@endif
