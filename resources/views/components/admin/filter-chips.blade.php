{{--
    One filter as a row of chips ("All" plus each option), each a toggle button with aria-pressed,
    so the choice is visible at a glance and reachable by keyboard. `field` is the filter key,
    `current` its value; chips call setFilter(field, value) on the Livewire screen. The choice
    lives in the address, so a filtered list can be bookmarked or shared.
--}}
@props(['field', 'label', 'options', 'current' => null])
<div {{ $attributes->class(['filter-chips']) }} role="group" aria-label="{{ $label }}" data-filter="{{ $field }}">
    <span class="filter-chips__label t-label" aria-hidden="true">{{ $label }}</span>
    <button type="button" class="filter-chip" wire:click="setFilter('{{ $field }}')" aria-pressed="{{ $current === null || $current === '' ? 'true' : 'false' }}">All</button>
    @foreach ($options as $value => $optionLabel)
        <button type="button" class="filter-chip" wire:click="setFilter('{{ $field }}', '{{ $value }}')" aria-pressed="{{ (string) $current === (string) $value ? 'true' : 'false' }}" data-filter-value="{{ $value }}">{{ $optionLabel }}</button>
    @endforeach
</div>
