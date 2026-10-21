{{--
    Closes a long page: the MK mark (ring, never the disc, so at least 32px) above a short
    centred brass rule. The mark carries its own clear space of one sixth of its diameter,
    so nothing is drawn inside it. `tone` is the ink of the letters, as on <x-brand.mark>.
--}}
@props(['tone' => \App\Constants\BrandMark::TONE_EVERGREEN, 'size' => 40])
@php
    $size = max(\App\Constants\BrandMark::DISC_BELOW_PX, (int) $size);
@endphp
<div {{ $attributes->class(['seal']) }} aria-hidden="true" data-rise>
    <x-brand.mark :size="$size" :tone="$tone" decorative />
    <span class="seal__rule"></span>
</div>
