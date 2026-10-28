{{-- The Sort menu of x-admin.list-toolbar: one choice of column and direction, as a small menu like a filter's. --}}
<div class="list-popover list-popover--end" x-data="listPopover" x-on:keydown.escape="close(true, $event)" x-on:click.outside="close(false)" x-on:focusout="leave($event)" data-sort-menu>
    <x-button variant="secondary" size="toolbar" type="button" @class(['list-toolbar__filter', 'is-active' => $active]) x-ref="button" x-on:click="toggle()" x-on:keydown.down.prevent="show()"
              aria-haspopup="menu" aria-expanded="false" x-bind:aria-expanded="open.toString()" aria-controls="list-sort-panel" aria-label="{{ $active ? __('Sort: :order', ['order' => $label]) : __('Sort') }}" data-sort-button data-toolbar-control="sort">
        <x-icon name="sort" class="size-4" /><span class="sr-only">{{ __('Sort') }}</span><x-icon name="chevron-down" class="size-3.5" />
    </x-button>
    <div class="list-popover__panel" id="list-sort-panel" role="menu" aria-label="{{ __('Sort') }}" x-ref="panel" x-show="open" x-cloak x-on:keydown="nav($event)" data-filter-panel>
        @foreach ($sorts as $sort)
            <button type="button" role="menuitemradio" aria-checked="{{ $sort['active'] ? 'true' : 'false' }}" tabindex="-1" id="list-sort-{{ $loop->index }}" class="list-popover__option"
                    wire:click="applySort(@js($sort['column']), @js($sort['direction']))" wire:key="sort-{{ $sort['column'] }}-{{ $sort['direction'] }}" data-sort="{{ $sort['column'] === '' ? 'default' : $sort['column'].':'.$sort['direction'] }}">
                <span class="list-popover__mark list-popover__mark--radio" aria-hidden="true"></span><span class="list-popover__label">{{ $sort['label'] }}</span>
            </button>
        @endforeach
    </div>
</div>
