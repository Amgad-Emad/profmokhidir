@php
    // Map luminance 0..1 onto evergreen -> ivory, per channel.
    $from = sscanf(\App\Constants\Brand::EVERGREEN, '#%02x%02x%02x');
    $to = sscanf(\App\Constants\Brand::IVORY, '#%02x%02x%02x');
    $table = fn (int $i): string => sprintf('%.4f %.4f', $from[$i] / 255, $to[$i] / 255);
@endphp
{{-- Evergreen → ivory duotone used by .photo-archive. Zero-size so it never affects layout. --}}
<svg width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute">
    <filter id="duotone-archive" color-interpolation-filters="sRGB">
        <feColorMatrix type="saturate" values="0" />
        <feComponentTransfer>
            <feFuncR type="table" tableValues="{{ $table(0) }}" />
            <feFuncG type="table" tableValues="{{ $table(1) }}" />
            <feFuncB type="table" tableValues="{{ $table(2) }}" />
        </feComponentTransfer>
    </filter>
</svg>
