{{--
    The sticky save bar of every edit screen: the status (Draft or Published, icon and text), the
    save state ("Saved 2 minutes ago", "Unsaved changes", "Saving…", or why an autosave waited),
    then History, Preview (a signed link to the real public page, saved first), and the actions:
    Save draft and Publish for a draft; Unpublish and Update once published. The buttons carry
    data-shortcut so Ctrl/Cmd+S and Ctrl/Cmd+Enter press them. With `draftable` (page copy, whose drafts
    sit beside the published page) a published record keeps Save draft beside Unpublish and
    `publish-label` (Publish changes), so Ctrl/Cmd+S never puts anything live. With `save-only`
    (the page editor) there is one Save button, and Ctrl/Cmd+S, that puts the changes live at once;
    History and Preview are left out there (the page editor has its side-by-side preview).
--}}
@props(['editor', 'saveLabel' => null, 'draftable' => false, 'publishLabel' => null, 'saveOnly' => false])
@use('App\Constants\ContentStatus')
@use('App\Constants\StatusTone')
@php
    $published = $editor['status'] === ContentStatus::Published;
@endphp
<div class="save-bar" role="region" aria-label="{{ __('Save') }}" data-save-bar>
    <div class="save-bar__state">
        <x-admin.status-badge :tone="StatusTone::forContent($editor['status'])" :label="$editor['status']->label()" data-save-status />
        <p class="save-bar__note" aria-live="polite" x-text="stateLabel" x-bind:data-tone="stateTone" data-save-note>{{ $editor['savedAt'] ? __('Saved') : __('Not saved yet') }}</p>
    </div>
    <div class="save-bar__actions">
        @if ($editor['revisionCount'] > 0 && ! $saveOnly)
            <x-button variant="quiet" size="sm" type="button" wire:click="toggleHistory" aria-haspopup="dialog" data-history-open>
                <x-icon name="history" class="size-4" />{{ __('History') }}
            </x-button>
        @endif
        @if ($editor['previewUrl'] && ! $saveOnly)
            <a href="{{ $editor['previewUrl'] }}" target="_blank" rel="noopener" class="save-bar__link" x-on:click="preview($event)" data-preview>
                <x-icon name="eye" class="size-4" />{{ __('Preview') }}<span class="sr-only"> {{ __('(opens the public page in a new tab)') }}</span>
            </a>
        @endif
        @if ($published && $editor['publicUrl'])
            <a href="{{ $editor['publicUrl'] }}" target="_blank" rel="noopener" class="save-bar__link" data-view-on-site>
                <x-icon name="external" class="size-4" />{{ __('View on site') }}<span class="sr-only"> {{ __('(opens in a new tab)') }}</span>
            </a>
        @endif
        @if ($saveOnly)
            <x-button type="button" variant="primary" wire:click="publish" wire:loading.attr="disabled" data-shortcut="save publish" data-save>{{ $saveLabel ?? __('Save') }}</x-button>
        @elseif ($published && $draftable)
            <x-button variant="quiet" size="sm" type="button" wire:click="unpublish" wire:loading.attr="disabled" data-unpublish>{{ __('Unpublish') }}</x-button>
            <x-button type="button" wire:click="save" wire:loading.attr="disabled" data-shortcut="save" data-save>{{ $saveLabel ?? __('Save draft') }}</x-button>
            <x-button type="button" variant="primary" wire:click="publish" wire:loading.attr="disabled" data-shortcut="publish" data-publish>{{ $publishLabel ?? __('Update') }}</x-button>
        @elseif ($published)
            <x-button variant="quiet" size="sm" type="button" wire:click="unpublish" wire:loading.attr="disabled" data-unpublish>{{ __('Unpublish') }}</x-button>
            <x-button type="button" variant="primary" wire:click="publish" wire:loading.attr="disabled" data-shortcut="save publish" data-save>{{ $saveLabel ?? __('Update') }}</x-button>
        @else
            <x-button type="button" wire:click="save" wire:loading.attr="disabled" data-shortcut="save" data-save>{{ $saveLabel ?? __('Save draft') }}</x-button>
            <x-button type="button" variant="primary" wire:click="publish" wire:loading.attr="disabled" data-shortcut="publish" data-publish>{{ __('Publish') }}</x-button>
        @endif
    </div>
</div>
