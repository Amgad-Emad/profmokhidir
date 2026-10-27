@use('App\Livewire\Admin\ContentIndex')
<div>
    @php
        $plural = __($type['plural']);
        $singularLower = mb_strtolower(__($type['singular']));
        $pluralLower = mb_strtolower($plural);
    @endphp
    <x-admin.page-header :eyebrow="__(\App\Constants\AdminNavigation::groups()[\App\Constants\AdminNavigation::get($type['nav'])['group']])" :title="$plural">
        <x-button variant="primary" :href="$createUrl" data-create><x-icon name="plus" class="size-4" />{{ __('New :type', ['type' => $singularLower]) }}</x-button>
    </x-admin.page-header>

    <x-admin.flash />
    @error('publish')
        <x-alert tone="danger" role="alert" class="mt-8">{{ $message }}</x-alert>
    @enderror

    {{-- Active / Trash tabs --}}
    <div class="mt-10 flex flex-wrap items-center gap-2 border-b border-rule" role="tablist" aria-label="{{ __('Records') }}">
        <button type="button" role="tab" wire:click="showActive" aria-selected="{{ $view === ContentIndex::VIEW_ACTIVE ? 'true' : 'false' }}" class="admin-tab" data-tab="active">{{ $plural }}</button>
        <button type="button" role="tab" wire:click="showTrash" aria-selected="{{ $view === ContentIndex::VIEW_TRASH ? 'true' : 'false' }}" class="admin-tab" data-tab="trash">{{ __('Trash') }}<span class="ms-2 text-sm text-ink-muted">@num($trashedCount)</span></button>
    </div>

    {{-- Search and filters: chips for short option lists, a select for long ones; all kept in the address. --}}
    <div class="list-tools mt-6">
        <form novalidate wire:submit.prevent role="search" aria-label="{{ __('Search :items', ['items' => $pluralLower]) }}" class="list-tools__search">
            <x-form.input type="search" model="search" id="search" live :label="__('Search')" :optional="false" loading="search" :hint="__('Press / to jump here.')"
                          :placeholder="__('Title or words in :items', ['items' => $pluralLower])" autocomplete="off" data-shortcut="search" />
        </form>
        @if ($view === ContentIndex::VIEW_ACTIVE)
            <div class="list-tools__filters">
                @foreach ($filterOptions as $field => $filter)
                    @if (count($filter['options']) <= $maxChips)
                        <x-admin.filter-chips :field="$field" :label="__($filter['label'])" :options="$filter['options']" :current="$filters[$field] ?? null" />
                    @else
                        <x-form.select :model="'filters.'.$field" :id="'filter-'.$field" live :label="__($filter['label'])" :optional="false" :placeholder="__('All')" :options="$filter['options']" :data-filter="$field" class="list-tools__select" />
                    @endif
                @endforeach
                <x-admin.filter-chips :field="$placeholderFilter" :label="__('Sample text')" :options="['1' => __('Still holds sample text')]" :current="$filters[$placeholderFilter] ?? null" />
                @if ($translationFilter)
                    <x-admin.filter-chips :field="$translationFilter" :label="__('Arabic')" :options="['1' => __('Needs Arabic translation')]" :current="$filters[$translationFilter] ?? null" />
                @endif
            </div>
        @endif
    </div>

    @if ($view === ContentIndex::VIEW_ACTIVE && ! $canReorder && ! $items->isEmpty())
        <p class="mt-4 text-sm font-normal text-ink-muted">{{ __('Clear the search, filters and sorting to change the display order.') }}
            @if ($sort !== '')<x-button variant="quiet" size="sm" type="button" class="ms-1" wire:click="clearSort">{{ __('Reset sorting') }}</x-button>@endif
        </p>
    @endif

    {{-- Table --}}
    @if ($items->isEmpty())
        @if ($view === ContentIndex::VIEW_TRASH)
            <x-empty-state :message="__('The trash is empty. Anything moved to the trash waits here until it is restored.')" />
        @else
            <x-admin.list-empty class="mt-8" :title="__('No :items yet', ['items' => $pluralLower])" :text="__($emptyText)" :create-url="$createUrl" :create-label="__('Add the first :type', ['type' => $singularLower])" :filtered="$hasFilters" />
        @endif
    @else
        @php $pageIds = $items->getCollection()->map(fn ($item) => (int) $item->getKey())->all(); $pageSelected = array_intersect($pageIds, $selected) === $pageIds; @endphp
        <div class="admin-table-wrap mt-6">
            <table class="admin-table w-full text-start text-sm" data-view="{{ $view }}">
                <caption class="sr-only">{{ $view === ContentIndex::VIEW_TRASH ? __(':items in the trash', ['items' => $plural]) : $plural }}</caption>
                <thead>
                    <tr>
                        @if ($view === ContentIndex::VIEW_ACTIVE)
                            <th scope="col" class="admin-table__check">
                                <input type="checkbox" class="admin-check" @checked($pageSelected)
                                       @if ($pageSelected) wire:click="clearSelection" @else wire:click="selectPage({{ json_encode($pageIds) }})" @endif
                                       aria-label="{{ __('Select every :type on this page', ['type' => $singularLower]) }}" data-select-page>
                            </th>
                        @endif
                        @if ($canReorder)
                            <th scope="col" class="w-8"><span class="sr-only">{{ __('Drag handle') }}</span></th>
                        @endif
                        @foreach ($columns as $column => $label)
                            <th scope="col" @if ($sort === $column) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
                                @if ($view === ContentIndex::VIEW_ACTIVE && in_array($column, $sortable, true))
                                    <button type="button" wire:click="sortBy('{{ $column }}')" class="admin-table__sort" data-sort="{{ $column }}">
                                        {{ __($label) }}
                                        @if ($sort === $column)<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                                    </button>
                                @else
                                    {{ __($label) }}
                                @endif
                            </th>
                        @endforeach
                        @if ($view === ContentIndex::VIEW_TRASH)
                            <th scope="col" class="text-end">{{ __('Deleted') }}</th>
                        @else
                            <th scope="col" class="text-end" @if ($sort === 'updated_at') aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
                                <button type="button" wire:click="sortBy('updated_at')" class="admin-table__sort" data-sort="updated_at">
                                    {{ __('Updated') }}
                                    @if ($sort === 'updated_at')<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                                </button>
                            </th>
                        @endif
                        <th scope="col" class="w-12"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody @if ($canReorder) x-data="sortableRows" x-on:drop.prevent="finish($event)" @endif>
                    @foreach ($items as $item)
                        @php
                            $editUrl = route($editRoute, [$routeParameter => $item->getKey()]);
                            $label = $this->cell($item, array_key_first($columns))['text'];
                            $publicUrl = $this->publicUrl($item);
                            $isSelected = in_array((int) $item->getKey(), $selected, true);
                        @endphp
                        <tr wire:key="row-{{ $item->getKey() }}" data-id="{{ $item->getKey() }}" @class(['is-selected' => $isSelected])
                            @if ($canReorder) draggable="true" x-on:dragstart="start($event, {{ $item->getKey() }})" x-on:dragover.prevent="over($event)" x-on:dragend="end($event)" @endif>
                            @if ($view === ContentIndex::VIEW_ACTIVE)
                                <td class="admin-table__check">
                                    <input type="checkbox" class="admin-check" value="{{ $item->getKey() }}" wire:model.live="selected" aria-label="{{ __('Select :item', ['item' => $label]) }}">
                                </td>
                            @endif
                            @if ($canReorder)
                                <td class="cursor-grab text-ink-muted" aria-hidden="true" title="{{ __('Drag to reorder') }}">⋮⋮</td>
                            @endif
                            @foreach ($columns as $column => $columnLabel)
                                @php $cell = $this->cell($item, $column); @endphp
                                <td @if ($loop->first) class="admin-table__title" @endif>
                                    @if ($loop->first && $view === ContentIndex::VIEW_ACTIVE)
                                        <a href="{{ $editUrl }}" dir="auto">{{ $cell['text'] }}</a>
                                        @php $missingArabic = $this->missingArabic($item); @endphp
                                        @if ($missingArabic !== [])
                                            <span class="ms-2 inline-flex items-center gap-1 whitespace-nowrap text-xs font-normal text-ink-muted" title="{{ __('Not yet in Arabic: :fields', ['fields' => implode(__(', '), $missingArabic)]) }}" data-needs-arabic>
                                                <x-icon name="info" class="size-3.5" />{{ __('Arabic missing') }}<span class="sr-only">: {{ implode(__(', '), $missingArabic) }}</span>
                                            </span>
                                        @endif
                                    @elseif (isset($cell['tone']))
                                        <x-admin.status-badge :tone="$cell['tone']" :label="$cell['text']" />
                                    @else
                                        <span dir="auto">{{ $cell['text'] }}</span>
                                    @endif
                                </td>
                            @endforeach
                            @php $stamp = $view === ContentIndex::VIEW_TRASH ? $item->deleted_at : $item->updated_at; @endphp
                            <td class="text-end whitespace-nowrap text-ink-muted">
                                @if ($stamp)<time datetime="{{ $stamp->toIso8601String() }}" title="{{ $stamp->translatedFormat(\App\Constants\DateFormats::LONG.', H:i') }}">{{ $stamp->diffForHumans() }}</time>@endif
                            </td>
                            <td class="text-end">
                                @if ($view === ContentIndex::VIEW_TRASH)
                                    <div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2">
                                        <x-button variant="quiet" size="sm" type="button" wire:click="restoreMany([{{ $item->getKey() }}])" data-restore>{{ __('Restore') }}<span class="sr-only"> {{ $label }}</span></x-button>
                                        <x-button variant="danger" size="sm" type="button" wire:click="confirm('{{ ContentIndex::ACTION_PURGE }}', {{ $item->getKey() }})" data-purge><x-icon name="trash" class="size-4" />{{ __(\App\Constants\ConfirmDialog::PURGE_LABEL) }}<span class="sr-only"> {{ $label }}</span></x-button>
                                    </div>
                                @else
                                    <x-admin.row-menu :label="$label">
                                        <a href="{{ $editUrl }}" class="row-menu__item"><x-icon name="feather" class="size-4" />{{ __('Edit') }}</a>
                                        @if ($publicUrl)
                                            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="row-menu__item" data-view-on-site><x-icon name="external" class="size-4" />{{ __('View on site') }}<span class="sr-only"> {{ __('(opens in a new tab)') }}</span></a>
                                        @endif
                                        @if ($item->isPublished())
                                            <button type="button" wire:click="unpublish({{ $item->getKey() }})" class="row-menu__item"><x-icon name="minus" class="size-4" />{{ __('Unpublish') }}</button>
                                        @else
                                            <button type="button" wire:click="publish({{ $item->getKey() }})" class="row-menu__item" data-publish-row><x-icon name="check" class="size-4" />{{ __('Publish') }}</button>
                                        @endif
                                        @if ($canReorder)
                                            <button type="button" wire:click="moveUp({{ $item->getKey() }})" @disabled($loop->first && $items->onFirstPage()) class="row-menu__item" data-move="up"><span aria-hidden="true" class="w-4 text-center">↑</span>{{ __('Move up') }}</button>
                                            <button type="button" wire:click="moveDown({{ $item->getKey() }})" @disabled($loop->last && ! $items->hasMorePages()) class="row-menu__item" data-move="down"><span aria-hidden="true" class="w-4 text-center">↓</span>{{ __('Move down') }}</button>
                                        @endif
                                        <button type="button" wire:click="trash({{ $item->getKey() }})" class="row-menu__item row-menu__item--danger" data-delete><x-icon name="trash" class="size-4" />{{ __('Move to trash') }}</button>
                                    </x-admin.row-menu>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $items->links('components.admin.pagination') }}
    @endif

    <x-admin.bulk-bar :count="count($selected)" />

    @if ($purging)
        <x-admin.confirm-dialog :show="true" danger :title="$purging['title']" action="purge" :confirm-label="$purging['confirm']" data-purge-dialog>
            {{ $purging['body'] }}
        </x-admin.confirm-dialog>
    @endif
</div>
