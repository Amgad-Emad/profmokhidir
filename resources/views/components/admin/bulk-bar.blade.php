{{--
    The bulk-action bar: sticks to the bottom of the screen while rows are ticked, with how many,
    Publish, Unpublish, Move to trash (undoable from the toast) and Clear selection.
--}}
@props(['count'])
@if ($count > 0)
    <div class="bulk-bar" role="region" aria-label="Selected rows" data-bulk-bar>
        <p class="bulk-bar__count" aria-live="polite"><span class="font-medium">{{ $count }}</span> selected</p>
        <div class="bulk-bar__actions">
            <button type="button" class="save-bar__link" wire:click="bulkPublish" wire:loading.attr="disabled" data-bulk="publish">Publish</button>
            <button type="button" class="save-bar__link" wire:click="bulkUnpublish" wire:loading.attr="disabled" data-bulk="unpublish">Unpublish</button>
            <button type="button" class="save-bar__link save-bar__link--danger" wire:click="bulkTrash" wire:loading.attr="disabled" data-bulk="trash"><x-icon name="trash" class="size-4" />Move to trash</button>
            <button type="button" class="save-bar__link" wire:click="clearSelection" data-bulk="clear">Clear selection</button>
        </div>
    </div>
@endif
