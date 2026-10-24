{{--
    The bulk-action bar: sticks to the bottom of the screen while rows are ticked, with how many,
    Publish, Unpublish, Move to trash (undoable from the toast) and Clear selection.
--}}
@props(['count'])
@if ($count > 0)
    <div class="bulk-bar" role="region" aria-label="Selected rows" data-bulk-bar>
        <p class="bulk-bar__count" aria-live="polite"><span class="font-medium">{{ $count }}</span> selected</p>
        <div class="bulk-bar__actions">
            <x-button variant="quiet" size="sm" type="button" wire:click="bulkPublish" wire:loading.attr="disabled" data-bulk="publish">Publish</x-button>
            <x-button variant="quiet" size="sm" type="button" wire:click="bulkUnpublish" wire:loading.attr="disabled" data-bulk="unpublish">Unpublish</x-button>
            <x-button variant="quiet" size="sm" danger type="button" wire:click="bulkTrash" wire:loading.attr="disabled" data-bulk="trash"><x-icon name="trash" class="size-4" />Move to trash</x-button>
            <x-button variant="quiet" size="sm" type="button" wire:click="clearSelection" data-bulk="clear">Clear selection</x-button>
        </div>
    </div>
@endif
