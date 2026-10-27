{{--
    The bulk-action bar: sticks to the bottom of the screen while rows are ticked, with how many,
    Publish, Unpublish, Move to trash (undoable from the toast) and Clear selection.
--}}
@props(['count'])
@if ($count > 0)
    <div class="bulk-bar" role="region" aria-label="{{ __('Selected rows') }}" data-bulk-bar>
        <p class="bulk-bar__count" aria-live="polite">{!! __(':count selected', ['count' => '<span class="font-medium">'.e(app(\App\Services\LocaleService::class)->digits($count)).'</span>']) !!}</p>
        <div class="bulk-bar__actions">
            <x-button variant="quiet" size="sm" type="button" wire:click="bulkPublish" wire:loading.attr="disabled" data-bulk="publish">{{ __('Publish') }}</x-button>
            <x-button variant="quiet" size="sm" type="button" wire:click="bulkUnpublish" wire:loading.attr="disabled" data-bulk="unpublish">{{ __('Unpublish') }}</x-button>
            <x-button variant="danger" size="sm" type="button" wire:click="bulkTrash" wire:loading.attr="disabled" data-bulk="trash"><x-icon name="trash" class="size-4" />{{ __('Move to trash') }}</x-button>
            <x-button variant="quiet" size="sm" type="button" wire:click="clearSelection" data-bulk="clear">{{ __('Clear selection') }}</x-button>
        </div>
    </div>
@endif
