{{--
    The button. One pill system (CLAUDE.md: pill-shaped, ivory filled or outline): on light surfaces the
    primary action is the outline pill (.btn-pill-outline, which fills evergreen on hover); on evergreen
    "filled-light" is the ivory pill and "outline-light" the outline. "outline" is the quiet secondary
    action (Back, Cancel): a text link in the metadata style with a 44px target.
--}}
@props(['variant' => 'filled', 'type' => 'submit', 'href' => null])
@php
    $styles = [
        'filled' => 'btn-pill-outline text-evergreen',
        'outline' => 'btn-quiet',
        'filled-light' => 'btn-pill-filled on-dark',
        'outline-light' => 'btn-pill-outline on-dark',
    ];
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $styles[$variant] ?? $styles['filled']]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $styles[$variant] ?? $styles['filled']]) }}>{{ $slot }}</button>
@endif
