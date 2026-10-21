{{--
    <x-museum-label title="Gezira research station" meta="Wad Madani, 1972" note="With the first cohort." />
    A caption set like a museum label: the title in Cormorant, place and year in small caps with
    old-style figures, and an optional one-line note. Renders a <figcaption> unless `as` says otherwise.
    `tone` is the ink: "evergreen" on ivory surfaces (default) or "ivory" on evergreen surfaces.
--}}
@props(['title' => null, 'meta' => null, 'note' => null, 'tone' => 'evergreen', 'as' => 'figcaption'])
@php $onDark = $tone === 'ivory'; @endphp
@if ($title || $meta || $note)
<{{ $as }} {{ $attributes->class(['museum-label', 'museum-label--ivory' => $onDark]) }}>
    @if ($title)
        <span class="museum-label__title">{{ $title }}</span>
    @endif
    @if ($meta)
        <span class="museum-label__meta smallcaps">{{ $meta }}</span>
    @endif
    @if ($note)
        <span class="museum-label__note">{{ $note }}</span>
    @endif
</{{ $as }}>
@endif
