{{--
    The sticky save bar of every edit screen: the status (Draft or Published, icon and text), the
    save state ("Saved 2 minutes ago", "Unsaved changes", "Saving…", or why an autosave waited),
    then History, Preview (a signed link to the real public page, saved first), and the actions:
    Save draft and Publish for a draft; Unpublish and Update once published. The buttons carry
    data-shortcut so Ctrl/Cmd+S and Ctrl/Cmd+Enter press them.
--}}
@props(['editor', 'saveLabel' => null])
@use('App\Constants\ContentStatus')
@use('App\Constants\StatusTone')
@php
    $published = $editor['status'] === ContentStatus::Published;
@endphp
<div class="save-bar" role="region" aria-label="Save" data-save-bar>
    <div class="save-bar__state">
        <x-admin.status-badge :tone="StatusTone::forContent($editor['status'])" :label="$editor['status']->label()" data-save-status />
        <p class="save-bar__note" aria-live="polite" x-text="stateLabel" x-bind:data-tone="stateTone" data-save-note>{{ $editor['savedAt'] ? 'Saved' : 'Not saved yet' }}</p>
    </div>
    <div class="save-bar__actions">
        @if ($editor['revisionCount'] > 0)
            <x-button variant="quiet" size="sm" type="button" wire:click="toggleHistory" aria-haspopup="dialog" data-history-open>
                <x-icon name="history" class="size-4" />History
            </x-button>
        @endif
        @if ($editor['previewUrl'])
            <a href="{{ $editor['previewUrl'] }}" target="_blank" rel="noopener" class="save-bar__link" x-on:click="preview($event)" data-preview>
                <x-icon name="eye" class="size-4" />Preview<span class="sr-only"> (opens the public page in a new tab)</span>
            </a>
        @endif
        @if ($published && $editor['publicUrl'])
            <a href="{{ $editor['publicUrl'] }}" target="_blank" rel="noopener" class="save-bar__link" data-view-on-site>
                <x-icon name="external" class="size-4" />View on site<span class="sr-only"> (opens in a new tab)</span>
            </a>
        @endif
        @if ($published)
            <x-button variant="quiet" size="sm" type="button" wire:click="unpublish" wire:loading.attr="disabled" data-unpublish>Unpublish</x-button>
            <x-button type="button" variant="primary" wire:click="publish" wire:loading.attr="disabled" data-shortcut="save publish" data-save>{{ $saveLabel ?? 'Update' }}</x-button>
        @else
            <x-button type="button" wire:click="save" wire:loading.attr="disabled" data-shortcut="save" data-save>{{ $saveLabel ?? 'Save draft' }}</x-button>
            <x-button type="button" variant="primary" wire:click="publish" wire:loading.attr="disabled" data-shortcut="publish" data-publish>Publish</x-button>
        @endif
    </div>
</div>
