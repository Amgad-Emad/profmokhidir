{{--
    A side drawer: a modal dialog sliding in from the right edge (full width on phones), with its
    title, a Close button and the content. Shown while `show` is true; Escape, Close and the
    backdrop call `close` (a Livewire method). Focus is trapped while it is open.
--}}
@props(['show' => false, 'title', 'close', 'id' => 'drawer', 'description' => null])
@if ($show)
    <div class="admin-drawer-backdrop" x-data x-on:click.self="$wire.{{ $close }}()" x-on:keydown.escape.window="$wire.{{ $close }}()" data-drawer="{{ $id }}">
        <div role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" {!! $description ? 'aria-describedby="'.e($id).'-description"' : '' !!} class="admin-drawer" x-trap.noscroll.inert="true">
            <div class="admin-drawer__header">
                <div>
                    <h2 id="{{ $id }}-title" class="font-display text-3xl font-light leading-tight">{{ $title }}</h2>
                    @if ($description)
                        <p id="{{ $id }}-description" class="mt-1 text-sm font-normal text-ink-muted">{{ $description }}</p>
                    @endif
                </div>
                <x-button variant="icon" type="button" wire:click="{{ $close }}"><span class="sr-only">Close</span><x-icon name="close" /></x-button>
            </div>
            <div class="admin-drawer__body">{{ $slot }}</div>
        </div>
    </div>
@endif
