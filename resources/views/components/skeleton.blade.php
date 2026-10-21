{{--
    Quiet placeholder for content still loading: linen bars on the text rhythm, the
    first a little shorter like a heading. Hidden from assistive technology; the
    live status text that accompanies it is announced instead. `tone="ivory"` on evergreen.
--}}
@props(['lines' => 3, 'tone' => 'evergreen'])
@php
    $lines = max(1, min(8, (int) $lines));
@endphp
<div {{ $attributes->class(['skeleton', 'skeleton--ivory' => $tone === 'ivory']) }} aria-hidden="true" data-skeleton>
    @for ($i = 0; $i < $lines; $i++)
        <span class="skeleton__line" style="--w: {{ $i === 0 ? 55 : ($i === $lines - 1 ? 70 : 92) }}%"></span>
    @endfor
</div>
