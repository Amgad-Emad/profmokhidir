{{-- Row actions of the content index: edit, view on site, publish/unpublish, move, trash. --}}
@props(['item', 'editUrl', 'publicUrl' => null, 'canReorder' => false, 'first' => false, 'last' => false, 'label'])
<div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2 text-sm">
    <a href="{{ $editUrl }}" class="link-meta">Edit<span class="sr-only"> {{ $label }}</span></a>
    @if ($publicUrl)
        <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="link-meta" data-view-on-site>View on site<span class="sr-only"> ({{ $label }}, opens in a new tab)</span></a>
    @endif
    @if ($item->isPublished())
        <button type="button" wire:click="unpublish({{ $item->getKey() }})" class="text-ink-muted underline-offset-4 hover:text-ink hover:underline">Unpublish<span class="sr-only"> {{ $label }}</span></button>
    @else
        <button type="button" wire:click="publish({{ $item->getKey() }})" class="text-success-tone underline-offset-4 hover:underline">Publish<span class="sr-only"> {{ $label }}</span></button>
    @endif
    @if ($canReorder)
        <span class="inline-flex items-center gap-1" data-move-buttons>
            <button type="button" wire:click="moveUp({{ $item->getKey() }})" @disabled($first) class="inline-flex size-8 items-center justify-center rounded-full border border-rule text-ink hover:border-focus disabled:opacity-40" title="Move up"><span aria-hidden="true">↑</span><span class="sr-only">Move {{ $label }} up</span></button>
            <button type="button" wire:click="moveDown({{ $item->getKey() }})" @disabled($last) class="inline-flex size-8 items-center justify-center rounded-full border border-rule text-ink hover:border-focus disabled:opacity-40" title="Move down"><span aria-hidden="true">↓</span><span class="sr-only">Move {{ $label }} down</span></button>
        </span>
    @endif
    <button type="button" wire:click="confirm('delete', {{ $item->getKey() }})" class="text-danger-tone underline-offset-4 hover:underline" data-delete>Delete<span class="sr-only"> {{ $label }}</span></button>
</div>
