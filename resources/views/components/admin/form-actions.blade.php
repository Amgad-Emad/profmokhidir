{{-- Save bar at the end of every form: save, back to the list, and the public link when published. --}}
@props(['indexUrl', 'publicUrl' => null, 'saveLabel' => 'Save'])
<div {{ $attributes->class(['mt-10 flex flex-wrap items-center gap-4 border-t border-rule pt-8']) }}>
    <x-button type="submit" wire:loading.attr="disabled" data-save>{{ $saveLabel }}</x-button>
    <x-button type="button" variant="outline" :href="$indexUrl">Back to the list</x-button>
    @if ($publicUrl)
        <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="link-meta ml-auto" data-view-on-site>View on site<span class="sr-only"> (opens in a new tab)</span></a>
    @endif
    <span class="text-sm font-light text-ink-muted" wire:loading wire:target="save">Saving…</span>
</div>
