{{-- The Columns menu of x-admin.list-toolbar's meta line: show or hide each column of the data table, remembered for the user. --}}
<div class="list-popover list-popover--end" x-data="listPopover" x-on:keydown.escape="close(true, $event)" x-on:click.outside="close(false)" x-on:focusout="leave($event)" data-columns>
    <x-button variant="quiet" size="sm" type="button" class="list-toolbar__columns" x-ref="button" x-on:click="toggle()" x-on:keydown.down.prevent="show()"
              aria-haspopup="menu" aria-expanded="false" x-bind:aria-expanded="open.toString()" aria-controls="data-table-columns" data-columns-button>
        <x-icon name="columns" class="size-4" />{{ __('data-table.columns') }}<x-icon name="chevron-down" class="size-3.5" />
    </x-button>
    <div class="list-popover__panel" id="data-table-columns" role="menu" aria-label="{{ __('data-table.columns_label') }}" x-ref="panel" x-show="open" x-cloak x-on:keydown="nav($event)" data-filter-panel data-columns-menu>
        @foreach ($table['menuColumns'] as $column)
            <button type="button" role="menuitemcheckbox" aria-checked="{{ $column['visible'] ? 'true' : 'false' }}" tabindex="-1" class="list-popover__option"
                    wire:click="setColumnVisible(@js($column['key']), {{ $column['visible'] ? 'false' : 'true' }})" wire:key="column-{{ $column['key'] }}" data-column-toggle="{{ $column['key'] }}">
                <span class="list-popover__mark" aria-hidden="true"></span><span class="list-popover__label">{{ $column['label'] }}</span>
            </button>
        @endforeach
        <button type="button" role="menuitem" tabindex="-1" class="list-popover__option list-popover__option--reset" wire:click="resetColumns" data-columns-reset>
            <span class="list-popover__mark list-popover__mark--none" aria-hidden="true"></span><span class="list-popover__label">{{ __('data-table.columns_reset') }}</span>
        </button>
    </div>
</div>
