{{--
    The keyboard shortcuts sheet, opened with "?" (or from the user menu): AdminUx::shortcuts() as
    a two-column list of keys and what they do. A modal dialog; Escape or Close dismisses it.
--}}
@use('App\Constants\AdminUx')
<div x-data="{ open: false }" x-on:admin-shortcuts-open.window="open = true" x-on:keydown.escape.window="open = false" data-shortcuts-sheet>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-surface-overlay p-4" x-show="open" x-cloak x-on:click.self="open = false">
        <div role="dialog" aria-modal="true" aria-labelledby="shortcuts-title" class="admin-dialog" x-trap.noscroll="open">
            <div class="flex items-start justify-between gap-4">
                <h2 id="shortcuts-title" class="font-display text-3xl font-light leading-tight">Keyboard shortcuts</h2>
                <x-button variant="icon" type="button" x-on:click="open = false"><span class="sr-only">Close</span><x-icon name="close" /></x-button>
            </div>
            <dl class="admin-shortcuts mt-6">
                @foreach (AdminUx::shortcuts() as $shortcut)
                    <div class="admin-shortcuts__row">
                        <dt>@foreach ($shortcut['keys'] as $key)<kbd class="admin-kbd" @if ($key === 'Ctrl') data-shortcut-modifier @endif>{{ $key }}</kbd>@endforeach</dt>
                        <dd>{{ $shortcut['label'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</div>
