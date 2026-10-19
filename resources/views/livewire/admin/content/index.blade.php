@use('App\Livewire\Admin\ContentIndex')
<div>
    <x-admin.page-header :eyebrow="\App\Constants\AdminNavigation::groups()[\App\Constants\AdminNavigation::get($type['nav'])['group']]" :title="$type['plural']">
        <x-button :href="$createUrl" data-create>New {{ strtolower($type['singular']) }}</x-button>
    </x-admin.page-header>

    <div class="mt-8">
        <x-admin.flash />
        @error('publish')
            <x-alert tone="bronze" role="alert" class="mb-6">{{ $message }}</x-alert>
        @enderror
    </div>

    {{-- Active / Trash tabs --}}
    <div class="flex flex-wrap items-center gap-2 border-b border-stone" role="tablist" aria-label="Records">
        <button type="button" role="tab" wire:click="showActive" aria-selected="{{ $view === ContentIndex::VIEW_ACTIVE ? 'true' : 'false' }}" class="admin-tab" data-tab="active">{{ $type['plural'] }}</button>
        <button type="button" role="tab" wire:click="showTrash" aria-selected="{{ $view === ContentIndex::VIEW_TRASH ? 'true' : 'false' }}" class="admin-tab" data-tab="trash">Trash<span class="ml-2 font-mono text-xs text-ash">{{ $trashedCount }}</span></button>
    </div>

    {{-- Search and filters --}}
    <form class="mt-6 grid gap-4 md:grid-cols-4" wire:submit.prevent role="search" aria-label="Search {{ strtolower($type['plural']) }}">
        <div class="md:col-span-2">
            <label for="search" class="t-label block text-bronze">Search</label>
            <input id="search" type="search" wire:model.live.debounce.400ms="search" placeholder="Search {{ strtolower($type['plural']) }}" class="mt-2 block w-full rounded-card border border-stone bg-ivory px-4 py-3 text-body text-evergreen placeholder:text-ash focus:border-bronze">
        </div>
        @if ($view === ContentIndex::VIEW_ACTIVE)
            @foreach ($filterOptions as $field => $filter)
                <div>
                    <label for="filter-{{ $field }}" class="t-label block text-bronze">{{ $filter['label'] }}</label>
                    <select id="filter-{{ $field }}" wire:model.live="filters.{{ $field }}" class="form-select mt-2 block w-full rounded-card border border-stone bg-ivory px-4 py-3 text-body text-evergreen focus:border-bronze" data-filter="{{ $field }}">
                        <option value="">All</option>
                        @foreach ($filter['options'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        @endif
    </form>

    @if ($view === ContentIndex::VIEW_ACTIVE && ! $canReorder)
        <p class="mt-4 text-sm font-light text-ash">Clear the search, filters and sorting to change the display order.
            @if ($sort !== '')<button type="button" wire:click="clearSort" class="ml-1 text-bronze underline-offset-4 hover:underline">Reset sorting</button>@endif
        </p>
    @endif

    {{-- Table --}}
    @if ($items->isEmpty())
        <x-empty-state :message="$view === ContentIndex::VIEW_TRASH ? 'The trash is empty.' : (trim($search) !== '' || array_filter($filters) !== [] ? 'Nothing matches your search.' : 'No '.strtolower($type['plural']).' yet. Create the first one.')" />
    @else
        <div class="mt-6 overflow-x-auto rounded-card border border-stone bg-ivory">
            <table class="admin-table w-full text-left text-sm" data-view="{{ $view }}">
                <caption class="sr-only">{{ $type['plural'] }}{{ $view === ContentIndex::VIEW_TRASH ? ' in the trash' : '' }}</caption>
                <thead>
                    <tr>
                        @if ($canReorder)
                            <th scope="col" class="w-10"><span class="sr-only">Drag handle</span></th>
                        @endif
                        @foreach ($columns as $column => $label)
                            <th scope="col" @if ($sort === $column) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
                                @if ($view === ContentIndex::VIEW_ACTIVE && in_array($column, $sortable, true))
                                    <button type="button" wire:click="sortBy('{{ $column }}')" class="inline-flex items-center gap-1 uppercase tracking-[0.2em] hover:text-evergreen" data-sort="{{ $column }}">
                                        {{ $label }}
                                        @if ($sort === $column)<span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>@endif
                                    </button>
                                @else
                                    {{ $label }}
                                @endif
                            </th>
                        @endforeach
                        <th scope="col" class="text-right">{{ $view === ContentIndex::VIEW_TRASH ? 'Deleted' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody @if ($canReorder) x-data="sortableRows" x-on:drop.prevent="finish($event)" @endif>
                    @foreach ($items as $item)
                        @php
                            $editUrl = route($editRoute, [$routeParameter => $item->getKey()]);
                            $label = $this->cell($item, array_key_first($columns))['text'];
                        @endphp
                        <tr wire:key="row-{{ $item->getKey() }}" data-id="{{ $item->getKey() }}"
                            @if ($canReorder) draggable="true" x-on:dragstart="start($event, {{ $item->getKey() }})" x-on:dragover.prevent="over($event)" x-on:dragend="end($event)" @endif>
                            @if ($canReorder)
                                <td class="cursor-grab text-ash" aria-hidden="true" title="Drag to reorder">⋮⋮</td>
                            @endif
                            @foreach ($columns as $column => $columnLabel)
                                @php $cell = $this->cell($item, $column); @endphp
                                <td @if ($loop->first) class="font-medium text-evergreen" @endif>
                                    @if ($loop->first && $view === ContentIndex::VIEW_ACTIVE)
                                        <a href="{{ $editUrl }}" class="no-underline hover:underline">{{ $cell['text'] }}</a>
                                    @elseif (isset($cell['tone']))
                                        <x-admin.status-badge :tone="$cell['tone']" :label="$cell['text']" />
                                    @else
                                        {{ $cell['text'] }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-right">
                                @if ($view === ContentIndex::VIEW_TRASH)
                                    <span class="mr-4 font-light text-ash">{{ $item->deleted_at?->diffForHumans() }}</span>
                                    <button type="button" wire:click="restore({{ $item->getKey() }})" class="text-cypress underline-offset-4 hover:underline" data-restore>Restore<span class="sr-only"> {{ $label }}</span></button>
                                @else
                                    <x-admin.table-actions :item="$item" :edit-url="$editUrl" :public-url="$this->publicUrl($item)" :can-reorder="$canReorder" :first="$loop->first && $items->onFirstPage()" :last="$loop->last && ! $items->hasMorePages()" :label="$label" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $items->links('components.admin.pagination') }}
    @endif

    <x-admin.confirm-dialog :show="$this->isConfirming('delete')" title="Move to the trash?" action="delete" confirm-label="Move to trash">
        The {{ strtolower($type['singular']) }} will leave the public site at once. You can restore it from the Trash tab.
    </x-admin.confirm-dialog>
</div>
