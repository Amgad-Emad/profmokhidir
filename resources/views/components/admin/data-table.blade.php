{{--
    The one table of every admin list (App\Livewire\Admin\Concerns\WithDataTable, App\Services\DataTableService,
    resources/css/data-table.css, resources/js/admin/data-table.js). `table` is the screen's dataTable($page, $rows).

    Columns come from the screen's Column definitions (only the visible ones): text at the start edge, numbers,
    dates and status at the end, the 12px label style in the header, sortable headers with an arrow and aria-sort.
    The first cells are the selection checkbox (or, in Reorder mode, the drag handle) and the primary cell:
    thumbnail, the title as a real link with its small badges, and a muted supporting line; both stick to the
    start edge when the table scrolls sideways, and the header sticks to the top. The whole row opens the edit
    screen on click; the row's actions show as icon buttons on hover or focus, with the "…" menu for the rest
    (always shown on touch screens). Under 768px each row is a card. While a search, filter, sort or page change
    is on its way, skeleton rows of the same height stand in. The footer gives "1–20 of 84", the page size and
    the pages. The `empty` slot replaces the table when there are no rows.
--}}
@props(['table', 'empty' => null])
@php
    $digits = app(\App\Services\LocaleService::class);
    $columns = $table['columns'];
    $rows = $table['rows'];
    $selectable = $table['selectable'];
    $reordering = $table['reordering'];
    $leading = $selectable || $reordering;
    $selected = $table['selected'];
    $remWidth = fn (?string $width): float => $width !== null && str_ends_with($width, 'rem') ? (float) $width : 0.0;
    // Fixed columns plus room for the primary cell: below this the table scrolls inside its frame.
    $minWidth = 16 + ($leading ? ($reordering ? 6.5 : 3) : 0) + ($table['hasActions'] ? 3.25 : 0)
        + array_sum(array_map(fn (array $entry): float => $entry['column']->isPrimary() ? 0 : ($remWidth($entry['column']->width) ?: 8), $columns));
    $typing = 'search,filters,setFilter,toggleFilter,removeFilter,applyView,showTab,applyFilters,clearFilters,clearSearch,applySort,sortBy,gotoPage,nextPage,previousPage,setPerPage,perPage,showTrash,showActive';
@endphp
<div {{ $attributes->class(['data-table']) }} id="{{ $table['id'] }}" x-data="dataTable" data-data-table data-layout="table"
     @if ($reordering) data-reordering @endif
     wire:loading.delay.class="is-loading" wire:target="{{ $typing }}">

    @if ($rows === [] && $empty !== null)
        {{ $empty }}
    @else
        {{-- Selection across pages --}}
        @if ($table['offerSelectAll'])
            <p class="data-table__notice" role="status" data-select-all-notice>
                {{ __('data-table.page_selected', ['count' => $digits->digits(count($table['pageIds']))]) }}
                <x-button variant="quiet" size="sm" type="button" wire:click="selectAllMatching" data-select-all-matching>{{ __('data-table.select_all_matching', ['count' => $digits->digits($table['matching'])]) }}</x-button>
            </p>
        @elseif ($table['allMatchingSelected'] && $table['matching'] > count($table['pageIds']))
            <p class="data-table__notice" role="status" data-all-selected-notice>
                {{ __('data-table.all_selected', ['count' => $digits->digits($table['matching'])]) }}
                <x-button variant="quiet" size="sm" type="button" wire:click="clearSelection" data-clear-selection>{{ __('data-table.clear_selection') }}</x-button>
            </p>
        @endif

        {{-- Reorder mode --}}
        @if ($reordering)
            <div class="data-table__notice data-table__notice--reorder" role="region" aria-label="{{ __('data-table.reorder') }}" data-reorder-bar>
                <p>{{ __('data-table.reorder_hint') }}</p>
                <div class="data-table__notice-actions">
                    <x-button variant="quiet" size="sm" type="button" wire:click="cancelReordering" data-reorder-cancel>{{ __('Cancel') }}</x-button>
                    <x-button variant="primary" size="sm" type="button" x-on:click="saveOrder()" wire:loading.attr="disabled" data-reorder-save>{{ __('data-table.save_order') }}</x-button>
                </div>
            </div>
        @endif

        <div class="data-table__frame" data-table-frame>
            <div class="data-table__scroll" x-ref="scroll" x-on:scroll.passive="edges()" tabindex="0" role="region" aria-label="{{ $table['caption'] }}" data-table-scroll>
                <table class="data-table__table" style="min-width: {{ $minWidth }}rem">
                    <caption class="sr-only">{{ $table['caption'] }}</caption>
                    <colgroup>
                        @if ($leading)<col class="data-table__col-lead" @if ($reordering) data-reorder-col @endif>@endif
                        @foreach ($columns as $entry)
                            <col @if ($entry['column']->width && ! $entry['column']->isPrimary()) style="width: {{ $entry['column']->width }}" @endif>
                        @endforeach
                        @if ($table['hasActions'])<col class="data-table__col-actions">@endif
                    </colgroup>
                    <thead>
                        <tr>
                            @if ($reordering)
                                <th scope="col" class="data-table__lead">{{ __('data-table.order') }}</th>
                            @elseif ($selectable)
                                <th scope="col" class="data-table__lead">
                                    @php $mixed = ! $table['pageSelected'] && array_intersect($table['pageIds'], $selected) !== []; @endphp
                                    <input type="checkbox" class="admin-check" @checked($table['pageSelected']) x-init="$el.indeterminate = {{ $mixed ? 'true' : 'false' }}"
                                           @if ($table['pageSelected']) wire:click="clearSelection" @else wire:click="selectPage({{ json_encode($table['pageIds']) }})" @endif
                                           aria-label="{{ __('data-table.select_page') }}" data-select-page>
                                </th>
                            @endif
                            @foreach ($columns as $entry)
                                @php
                                    $column = $entry['column'];
                                    $sorted = $table['sort'] === $column->key;
                                @endphp
                                <th scope="col" @class(['data-table__head', 'data-table__primary' => $column->isPrimary(), 'is-end' => $column->align->value === 'end'])
                                    data-priority="{{ $column->priority }}" data-column="{{ $column->key }}"
                                    @if ($sorted) aria-sort="{{ $table['direction'] === 'asc' ? 'ascending' : 'descending' }}" @endif>
                                    @if ($entry['sortable'])
                                        <button type="button" class="data-table__sort" wire:click="sortBy(@js($column->key))" data-sort="{{ $column->key }}">
                                            <span>{{ $column->label }}</span>
                                            <x-icon :name="$sorted && $table['direction'] === 'desc' ? 'arrow-down' : 'arrow-up'" @class(['data-table__sort-icon size-3.5', 'is-active' => $sorted]) />
                                            @unless ($sorted)<span class="sr-only">{{ __('data-table.sortable') }}</span>@endunless
                                        </button>
                                    @else
                                        {{ $column->label }}
                                    @endif
                                </th>
                            @endforeach
                            @if ($table['hasActions'])
                                <th scope="col" class="data-table__actions-head"><span class="sr-only">{{ __('Actions') }}</span></th>
                            @endif
                        </tr>
                    </thead>

                    <tbody class="data-table__rows" x-ref="rows" x-on:click="open($event)"
                           @if ($reordering) x-on:dragover.prevent="over($event)" x-on:drop.prevent="drop($event)" @endif>
                        @foreach ($rows as $row)
                            @php
                                $id = $row->id;
                                $isSelected = in_array((int) $id, $selected, true);
                                $detailId = $table['id'].'-detail-'.$id;
                                $rowAttributes = collect($row->attributes)->map(fn ($value, $key) => e($key).'="'.e((string) $value).'"')->implode(' ');
                            @endphp
                            <tr wire:key="{{ $table['id'] }}-row-{{ $id }}" @class(['data-table__row', 'is-selected' => $isSelected, 'has-link' => $row->url !== null])
                                data-row data-id="{{ $id }}" @if ($row->url) data-href="{{ $row->url }}" @endif {!! $rowAttributes !!}
                                @if ($reordering) draggable="true" x-on:dragstart="start($event)" x-on:dragend="end($event)" @endif>
                                @if ($reordering)
                                    <td class="data-table__lead data-table__handle" data-cell="handle">
                                        <span class="data-table__grip" aria-hidden="true" title="{{ __('data-table.drag') }}"><x-icon name="grip" class="size-5" /></span>
                                        <button type="button" class="data-table__move" x-on:click="move($event, -1)" data-move="up"><x-icon name="arrow-up" class="size-4" /><span class="sr-only">{{ __('Move up') }}: {{ $row->title }}</span></button>
                                        <button type="button" class="data-table__move" x-on:click="move($event, 1)" data-move="down"><x-icon name="arrow-down" class="size-4" /><span class="sr-only">{{ __('Move down') }}: {{ $row->title }}</span></button>
                                    </td>
                                @elseif ($selectable)
                                    <td class="data-table__lead" data-cell="select">
                                        @if ($row->selectable)
                                            <input type="checkbox" class="admin-check" value="{{ $id }}" @checked($isSelected) x-on:click="tick($event, {{ (int) $id }})"
                                                   aria-label="{{ __('Select :item', ['item' => $row->title]) }}" data-select-row>
                                        @endif
                                    </td>
                                @endif

                                @foreach ($columns as $entry)
                                    @php $column = $entry['column']; @endphp
                                    @if ($column->isPrimary())
                                        <td class="data-table__primary" data-cell="{{ $column->key }}" data-cell-type="primary">
                                            <div class="data-table__primary-inner">
                                                @if ($row->thumbnail)
                                                    <img src="{{ $row->thumbnail }}" alt="" class="data-table__thumb" loading="lazy" decoding="async" width="40" height="40">
                                                @endif
                                                <div class="data-table__text">
                                                    <p class="data-table__title-line">
                                                        @if ($row->url && ! $reordering)
                                                            <a href="{{ $row->url }}" @class(['data-table__title', 'font-mono' => $row->titleMono]) title="{{ $row->title }}" dir="auto" data-row-link>{{ $row->title }}</a>
                                                        @else
                                                            <span @class(['data-table__title', 'font-mono' => $row->titleMono]) title="{{ $row->title }}" dir="auto">{{ $row->title }}</span>
                                                        @endif
                                                        @foreach ($row->badges as $badge)
                                                            <span {{ new \Illuminate\View\ComponentAttributeBag(array_filter(['class' => 'data-table__badge data-table__badge--'.$badge['tone']->value, 'title' => $badge['title'] ?? null, 'lang' => $badge['lang'] ?? null, ...(isset($badge['hook']) ? [$badge['hook'] => ''] : []), ...($badge['data'] ?? [])], fn ($value) => $value !== null)) }}>{{ $badge['label'] }}</span>
                                                        @endforeach
                                                    </p>
                                                    @if ($row->supportingLine() !== '')
                                                        <p class="data-table__supporting" title="{{ $row->supportingLine() }}" dir="auto">{{ $row->supportingLine() }}</p>
                                                    @endif
                                                </div>
                                                @if ($row->detail)
                                                    <button type="button" class="data-table__disclose" x-on:click="toggle({{ json_encode((string) $id) }})"
                                                            aria-controls="{{ $detailId }}" aria-expanded="{{ $row->open ? 'true' : 'false' }}" x-bind:aria-expanded="isOpen({{ json_encode((string) $id) }}, {{ $row->open ? 'true' : 'false' }}).toString()" data-disclose>
                                                        <x-icon name="chevron-down" class="size-4" /><span class="sr-only">{{ $row->detailLabel ?? __('data-table.details') }}: {{ $row->title }}</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    @else
                                        @include('admin.data-table.cell', ['cell' => $row->cell($column->key), 'column' => $column, 'title' => $row->title])
                                    @endif
                                @endforeach

                                @if ($table['hasActions'])
                                    <td class="data-table__actions" data-cell="actions">
                                        @unless ($reordering)
                                            @include('admin.data-table.actions', ['row' => $row])
                                        @endunless
                                    </td>
                                @endif
                            </tr>
                            @if ($row->detail && ! $reordering)
                                <tr id="{{ $detailId }}" class="data-table__detail" wire:key="{{ $table['id'] }}-detail-{{ $id }}" x-bind:hidden="! isOpen({{ json_encode((string) $id) }}, {{ $row->open ? 'true' : 'false' }})" data-row-detail="{{ $id }}">
                                    <td colspan="{{ count($columns) + ($leading ? 1 : 0) + ($table['hasActions'] ? 1 : 0) }}">
                                        <div class="data-table__detail-body">{{ $row->detail }}</div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>

                    {{-- Loading: rows of the same height while the next page, sort or filter arrives --}}
                    <tbody class="data-table__skeleton" aria-hidden="true">
                        @for ($i = 0; $i < $table['skeleton']; $i++)
                            <tr class="data-table__row">
                                @if ($leading)<td class="data-table__lead"><span class="data-table__bone data-table__bone--box"></span></td>@endif
                                @foreach ($columns as $entry)
                                    <td @class(['data-table__primary' => $entry['column']->isPrimary(), 'is-end' => $entry['column']->align->value === 'end']) data-priority="{{ $entry['column']->priority }}">
                                        <span @class(['data-table__bone', 'data-table__bone--wide' => $entry['column']->isPrimary()])></span>
                                    </td>
                                @endforeach
                                @if ($table['hasActions'])<td></td>@endif
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        @if ($table['page'] !== null && $table['summary']['total'] > 0)
            @include('admin.data-table.footer', ['table' => $table])
        @endif
    @endif
</div>
