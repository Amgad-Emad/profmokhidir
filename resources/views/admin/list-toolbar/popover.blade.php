{{--
    One filter of x-admin.list-toolbar: its button ("Status", or "Status: Draft" once set) and a
    small menu of options with counts. Arrow keys, Home and End move through the options, Space
    or Enter chooses one (the menu stays open), Escape closes it and focus returns to the button.
    A single-choice filter starts with "Any", which clears it.
--}}
@php
    $panelId = 'filter-'.$filter['key'].'-panel';
    $single = $filter['type'] === \App\Constants\ListFilterType::Single->value;
@endphp
<div class="list-popover" x-data="listPopover" x-on:keydown.escape="close(true, $event)" x-on:click.outside="close(false)" x-on:focusout="leave($event)" wire:key="filter-{{ $filter['key'] }}" data-filter="{{ $filter['key'] }}">
    <x-button variant="secondary" size="toolbar" type="button" @class(['list-toolbar__filter', 'is-active' => $filter['active']]) x-ref="button" x-on:click="toggle()" x-on:keydown.down.prevent="show()"
              aria-haspopup="menu" aria-expanded="false" x-bind:aria-expanded="open.toString()" aria-controls="{{ $panelId }}" data-filter-button data-toolbar-control="filter-{{ $filter['key'] }}">
        <span class="list-toolbar__filter-label" title="{{ $filter['button'] }}">{{ $filter['button'] }}</span><x-icon name="chevron-down" class="size-3.5" />
    </x-button>
    <div class="list-popover__panel" id="{{ $panelId }}" role="menu" aria-label="{{ $filter['label'] }}" x-ref="panel" x-show="open" x-cloak x-on:keydown="nav($event)" data-filter-panel>
        @if ($single)
            <button type="button" role="menuitemradio" aria-checked="{{ $filter['active'] ? 'false' : 'true' }}" tabindex="-1" id="{{ $panelId }}-any" class="list-popover__option" wire:click="setFilter(@js($filter['key']))" data-filter-option="">
                <span class="list-popover__mark list-popover__mark--radio" aria-hidden="true"></span><span class="list-popover__label">{{ __('Any') }}</span>
            </button>
        @endif
        @foreach ($filter['options'] as $option)
            <button type="button" role="{{ $filter['role'] }}" aria-checked="{{ $option['checked'] ? 'true' : 'false' }}" tabindex="-1" id="{{ $panelId }}-{{ $loop->index }}" class="list-popover__option"
                    wire:click="toggleFilter(@js($filter['key']), @js($option['value']))" wire:key="filter-{{ $filter['key'] }}-{{ $option['value'] }}" data-filter-option="{{ $option['value'] }}">
                <span @class(['list-popover__mark', 'list-popover__mark--radio' => $single]) aria-hidden="true"></span>
                <span class="list-popover__label" dir="auto">{{ $option['label'] }}</span>
                @if ($option['count'] !== null)<span class="list-popover__count">@num($option['count'])</span>@endif
            </button>
        @endforeach
    </div>
</div>
