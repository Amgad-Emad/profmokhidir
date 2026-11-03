{{--
    The compact band above every admin list (App\Livewire\Admin\Concerns\WithListToolbar,
    App\Services\ListToolbarService, resources/css/list-toolbar.css, resources/js/admin/list-toolbar.js).
    First line: the views as tabs, each with its count. Second line: the search (searching as you
    type, "/" to jump in, Escape to clear), a button per filter opening a small popover of options
    with counts, the Sort menu, the density toggle and the New button; while rows are ticked the
    bulk bar takes the same place. Third line: the active filters as removable chips, "Clear all"
    and "Showing 7 of 24". When the band is narrow the filter buttons fold into one "Filters (2)"
    button (also whenever the set filters' longer labels no longer fit on the line) opening a sheet (a side sheet, or a bottom sheet on phones that also holds sort and
    density) with Apply. `toolbar` is the screen's listToolbar($shown); `items` the plural noun
    ("publications"); `selected` the ticked rows; the `bulk` slot holds the bulk actions; `table` the
    screen's dataTable() description, which adds the Reorder toggle and, beside the count, the Columns menu.
--}}
@props([
    'toolbar',
    'items',
    'placeholder' => null,
    'createUrl' => null,
    'createLabel' => null,
    'selected' => 0,
    'results' => 'list-results',
    'table' => null,
])
@php
    $digits = app(\App\Services\LocaleService::class);
    $sheetFilters = $toolbar['filters'];
    $buttonFilters = array_values(array_filter($toolbar['filters'], fn (array $filter): bool => $filter['toolbar']));
    $hasFilters = $sheetFilters !== [] && ! $toolbar['trash'];
    $hasSorts = $toolbar['sorts'] !== [] && ! $toolbar['trash'];
    $bulking = $selected > 0 && isset($bulk);
    $activeView = collect($toolbar['views'])->firstWhere('active', true)['key'] ?? null;
@endphp
<section {{ $attributes->class(['list-toolbar']) }} aria-label="{{ __('Views, search and filters of the :items', ['items' => $items]) }}" x-data="listToolbar" x-on:list-filtered.window="keepInView()" data-list-toolbar>
    {{-- Views --}}
    @if ($toolbar['views'] !== [])
        <div class="list-toolbar__views" role="tablist" aria-label="{{ __('Views') }}" x-on:keydown="tabKeys($event)" data-list-views>
            @foreach ($toolbar['views'] as $view)
                @php $selectedTab = $view['active'] || ($activeView === null && $loop->first); @endphp
                <button type="button" role="tab" id="list-view-{{ $view['key'] }}" aria-selected="{{ $view['active'] ? 'true' : 'false' }}" aria-controls="{{ $results }}" tabindex="{{ $selectedTab ? '0' : '-1' }}"
                        class="list-toolbar__view" wire:click="applyView(@js($view['key']))" wire:key="list-view-{{ $view['key'] }}" data-view="{{ $view['key'] }}">
                    {{ $view['label'] }}<span class="list-toolbar__view-count">@num($view['count'])</span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Toolbar, or the bulk bar in the same place --}}
    <div @class(['list-toolbar__row', 'list-toolbar__row--bulk' => $bulking]) x-bind:class="{ 'is-folded': folded }" x-ref="row" data-toolbar-row>
        @if ($bulking)
            <div class="list-toolbar__bulk" role="region" aria-label="{{ __('Selected rows') }}" data-bulk-bar>
                <p class="list-toolbar__selected" aria-live="polite">{{ __(':count selected', ['count' => $digits->digits($selected)]) }}</p>
                <div class="list-toolbar__bulk-actions">
                    {{ $bulk }}
                    <x-button variant="quiet" size="sm" type="button" wire:click="clearSelection" data-bulk="clear">{{ __('Cancel') }}</x-button>
                </div>
            </div>
        @else
            <form novalidate wire:submit.prevent role="search" aria-label="{{ __('Search the :items', ['items' => $items]) }}" class="list-toolbar__search" data-list-search>
                <x-form.input type="search" model="search" :live="\App\Constants\ListToolbar::SEARCH_DEBOUNCE_MS" id="list-search" :label="__('Search the :items', ['items' => $items])" hide-label size="compact"
                              :optional="false" loading="search" :placeholder="$placeholder ?? __('Search')" autocomplete="off" data-shortcut="search" :frame="['data-toolbar-control' => 'search']"
                              x-on:keydown.escape="if ($el.value !== '') { $event.preventDefault(); $event.stopPropagation(); $el.value = ''; $wire.set('search', '') }">
                    <x-slot:leading><x-icon name="search" class="list-toolbar__search-icon size-4" /></x-slot:leading>
                    <x-slot:action>
                        <kbd class="list-toolbar__key" aria-hidden="true">/</kbd>
                        <button type="button" class="list-toolbar__clear" x-on:click="const field = $el.closest('.form-control').querySelector('input'); field.value = ''; $wire.set('search', ''); field.focus()" data-search-clear>
                            <x-icon name="close" class="size-4" /><span class="sr-only">{{ __('Clear the search') }}</span>
                        </button>
                    </x-slot:action>
                </x-form.input>
            </form>

            @if ($hasFilters)
                <div class="list-toolbar__filters" role="group" aria-label="{{ __('Filters') }}" data-filter-buttons>
                    @foreach ($buttonFilters as $filter)
                        @include('admin.list-toolbar.popover', ['filter' => $filter])
                    @endforeach
                </div>
                <x-button variant="secondary" size="toolbar" type="button" class="list-toolbar__sheet-button" x-on:click="openSheet()" aria-haspopup="dialog" data-filters-sheet-button data-toolbar-control="filters">
                    <x-icon name="sliders" class="size-4" />{{ $toolbar['activeFilters'] > 0 ? __('Filters (:count)', ['count' => $digits->digits($toolbar['activeFilters'])]) : __('Filters') }}
                </x-button>
            @endif

            <div class="list-toolbar__end">
                @isset($tools){{ $tools }}@endisset
                @if ($table !== null && ! $toolbar['trash'])
                    @include('admin.data-table.controls', ['table' => $table])
                @endif
                @if ($hasSorts)
                    @include('admin.list-toolbar.sort', ['sorts' => $toolbar['sorts'], 'label' => $toolbar['sortLabel'], 'active' => $toolbar['sortActive']])
                @endif
                @unless ($toolbar['trash'])
                    <x-button variant="icon" size="toolbar" type="button" class="list-toolbar__density" wire:click="toggleDensity" aria-pressed="{{ $toolbar['density'] === 'compact' ? 'true' : 'false' }}" data-toolbar-control="density" data-density-toggle>
                        <x-icon :name="$toolbar['density'] === 'compact' ? 'rows-compact' : 'rows'" class="size-5" /><span class="sr-only">{{ __('Compact rows') }}</span>
                    </x-button>
                @endunless
                @if ($createUrl)
                    <x-button variant="primary" size="toolbar" :href="$createUrl" class="list-toolbar__create" data-create data-toolbar-control="create"><x-icon name="plus" class="size-4" />{{ $createLabel ?? __('New') }}</x-button>
                @endif
            </div>
        @endif
    </div>

    {{-- Active filters and the result count --}}
    <div class="list-toolbar__meta">
        @if ($toolbar['chips'] !== [])
            <ul class="list-toolbar__chips" aria-label="{{ __('Active filters') }}">
                @foreach ($toolbar['chips'] as $chip)
                    <li wire:key="chip-{{ $chip['key'] }}-{{ $chip['value'] }}">
                        <button type="button" class="list-chip" wire:click="removeFilter(@js($chip['key']), @js($chip['value']))" data-filter-chip="{{ $chip['key'] }}">
                            <span><span class="sr-only">{{ __('Remove the filter') }} </span>{{ $chip['label'] }}</span><x-icon name="close" class="size-3.5" />
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($toolbar['filtered'])
            <x-button variant="quiet" size="sm" type="button" class="list-toolbar__clear-all" wire:click="clearFilters" data-clear-filters>{{ __('Clear all') }}</x-button>
        @endif
        @if ($table !== null && ! $toolbar['trash'] && $table['menuColumns'] !== [])
            @include('admin.data-table.columns', ['table' => $table])
        @endif
        <p class="list-toolbar__result" aria-live="polite" data-result-count>{{ __('Showing :shown of :total', ['shown' => $digits->digits($toolbar['shown']), 'total' => $digits->digits($toolbar['total'])]) }}</p>
    </div>

    @if ($hasFilters)
        @include('admin.list-toolbar.sheet', ['toolbar' => $toolbar, 'sorts' => $hasSorts ? $toolbar['sorts'] : []])
    @endif
</section>
