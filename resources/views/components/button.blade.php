@props(['variant' => 'filled', 'type' => 'submit', 'href' => null])
@php
    $base = 'inline-flex items-center justify-center rounded-full px-7 py-3 text-base font-medium transition-colors duration-300 motion-reduce:transition-none';
    $styles = [
        'filled' => 'bg-evergreen text-ivory hover:bg-cypress',
        'outline' => 'border border-evergreen text-evergreen hover:bg-linen',
        'filled-light' => 'bg-ivory text-evergreen hover:bg-linen on-dark',
        'outline-light' => 'border border-champagne text-champagne hover:border-ivory hover:text-ivory on-dark',
    ];
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base.' '.$styles[$variant]]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base.' '.$styles[$variant]]) }}>{{ $slot }}</button>
@endif
