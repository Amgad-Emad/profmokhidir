{{--
    The empty state of an admin list: what goes here, in plain words, and the button that adds
    the first one. With a search or filters on (`filtered`) it says no items match and offers
    "Clear filters" instead (the screen's clearFilters(), which clears the search too), so an
    empty result never reads like an empty list.
--}}
@props(['title', 'text', 'createUrl' => null, 'createLabel' => null, 'filtered' => false])
<div {{ $attributes->class(['list-empty']) }} data-list-empty{!! $filtered ? ' data-list-no-match' : '' !!}>
    <x-ornament class="list-empty__ornament" />
    <h2 class="list-empty__title">{{ $filtered ? __('No items match these filters') : $title }}</h2>
    <p class="list-empty__text">{{ $filtered ? __('Try fewer filters or other words, or clear them to see everything.') : $text }}</p>
    <div class="list-empty__actions">
        @if ($filtered)
            <x-button type="button" wire:click="clearFilters" data-clear-filters>{{ __('Clear filters') }}</x-button>
        @elseif ($createUrl)
            <x-button variant="primary" :href="$createUrl" data-create>{{ $createLabel }}</x-button>
        @endif
    </div>
</div>
