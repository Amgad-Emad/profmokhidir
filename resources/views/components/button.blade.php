{{--
    The button. One pill system (CLAUDE.md: pill-shaped, ivory filled or outline, and since the sign-in
    redesign evergreen-filled on ivory surfaces for the one primary action of a form): on light surfaces
    the default is the outline pill (.btn-pill-outline, which fills evergreen on hover); "primary" is the
    solid evergreen pill (.btn-pill-solid) for a form's main action on ivory; on evergreen "filled-light"
    is the ivory pill and "outline-light" the outline. "outline" is the quiet secondary action (Back,
    Cancel): a text link in the metadata style with a 44px target. `busy` is the label the form shows
    while it submits (authForm swaps it in and blocks a second submit).
--}}
@props(['variant' => 'filled', 'type' => 'submit', 'href' => null, 'busy' => null])
@php
    $styles = [
        'filled' => 'btn-pill-outline text-ink',
        'primary' => 'btn-pill-solid',
        'outline' => 'btn-quiet',
        'filled-light' => 'btn-pill-filled on-dark',
        'outline-light' => 'btn-pill-outline on-dark',
    ];
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $styles[$variant] ?? $styles['filled']]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $styles[$variant] ?? $styles['filled'], 'data-busy-label' => $busy]) }}>{{ $slot }}</button>
@endif
