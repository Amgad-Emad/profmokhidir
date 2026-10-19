{{--
    Modal confirmation. Shown while `show` is true; Escape or Cancel calls `cancel`,
    the confirm button calls `action` (both Livewire method names). Focus is trapped.
--}}
@props(['show' => false, 'title', 'action', 'cancel' => 'cancelConfirmation', 'confirmLabel' => 'Yes, continue', 'cancelLabel' => 'Cancel'])
@if ($show)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-evergreen/70 p-4" x-data x-trap.noscroll.inert="true" x-on:keydown.escape.window="$wire.{{ $cancel }}()" data-confirm-dialog>
        <div role="dialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-description" class="w-full max-w-md rounded-card border border-stone bg-ivory p-6 shadow-none sm:p-8">
            <h2 id="confirm-title" class="font-display text-3xl font-light leading-tight">{{ $title }}</h2>
            <div id="confirm-description" class="mt-3 text-base font-light text-evergreen">{{ $slot }}</div>
            <div class="mt-8 flex flex-wrap justify-end gap-3">
                <x-button type="button" variant="outline" wire:click="{{ $cancel }}">{{ $cancelLabel }}</x-button>
                <x-button type="button" wire:click="{{ $action }}" wire:loading.attr="disabled" data-confirm>{{ $confirmLabel }}</x-button>
            </div>
        </div>
    </div>
@endif
