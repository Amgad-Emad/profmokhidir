{{--
    The empty state of an admin list: what goes here, in plain words, and the button that adds
    the first one. With filters on, it says nothing matches and offers to clear them instead.
--}}
@props(['title', 'text', 'createUrl' => null, 'createLabel' => null, 'filtered' => false])
<div {{ $attributes->class(['list-empty']) }} data-list-empty>
    <x-ornament class="list-empty__ornament" />
    <h2 class="list-empty__title">{{ $filtered ? __('Nothing matches') : $title }}</h2>
    <p class="list-empty__text">{{ $filtered ? __('No records match this search and these filters.') : $text }}</p>
    <div class="list-empty__actions">
        @if ($filtered)
            <x-button type="button" wire:click="clearFilters">{{ __('Clear search and filters') }}</x-button>
        @elseif ($createUrl)
            <x-button variant="primary" :href="$createUrl" data-create>{{ $createLabel }}</x-button>
        @endif
    </div>
</div>
