@props(['size' => 48, 'tone' => 'dark'])
@php
    $small = $size < 32;
    $label = 'Professor Mohamed Osman Khidir';
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full font-display font-light select-none']) }}
      style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.42) }}px; margin: {{ round($size / 6) }}px;
             @if($small) background-color: var(--color-evergreen); color: var(--color-ivory);
             @else border: 1px solid var(--color-brass); color: {{ $tone === 'dark' ? 'var(--color-champagne)' : 'var(--color-evergreen)' }}; @endif"
      role="img" aria-label="{{ $label }}">MK</span>
