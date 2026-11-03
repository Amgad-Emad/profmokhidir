{{--
    The Reorder toggle of x-admin.list-toolbar on screens kept in a display order (only while nothing
    is searched, filtered or sorted, since the order is changed on the full list).
--}}
@if ($table['reorderable'])
    @if ($table['reordering'])
        <x-button variant="icon" size="toolbar" type="button" class="list-toolbar__toggle" wire:click="cancelReordering" aria-pressed="true" :title="__('data-table.reorder')" data-reorder-toggle data-toolbar-control="reorder">
            <x-icon name="grip" class="size-5" /><span class="sr-only">{{ __('data-table.reorder') }}</span>
        </x-button>
    @elseif ($table['canReorder'])
        <x-button variant="icon" size="toolbar" type="button" class="list-toolbar__toggle" wire:click="startReordering" aria-pressed="false" :title="__('data-table.reorder')" data-reorder-toggle data-toolbar-control="reorder">
            <x-icon name="grip" class="size-5" /><span class="sr-only">{{ __('data-table.reorder') }}</span>
        </x-button>
    @endif
@endif
