{{--
    The archive duotone used by .photo-archive: luminance 0..1 mapped onto the theme's duotone
    shadow -> highlight (evergreen -> ivory in the brand), one filter per mode because a colour
    theme may give the dark mode its own ends (App\Services\ColourThemeService). Zero-size, so it
    never affects layout.
--}}
@inject('colourThemes', 'App\Services\ColourThemeService')
@php
    $palette = $colourThemes->paletteFor(auth()->user());
    $table = function (string $mode, int $channel) use ($palette): string {
        $from = \App\Support\Colour::parse($palette[$mode]['duotone-shadow']);
        $to = \App\Support\Colour::parse($palette[$mode]['duotone-highlight']);

        return sprintf('%.4f %.4f', $from[$channel], $to[$channel]);
    };
@endphp
<svg width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute">
    @foreach (['light' => 'duotone-archive', 'dark' => 'duotone-archive-dark'] as $mode => $id)
        <filter id="{{ $id }}" color-interpolation-filters="sRGB">
            <feColorMatrix type="saturate" values="0" />
            <feComponentTransfer>
                <feFuncR type="table" tableValues="{{ $table($mode, 0) }}" />
                <feFuncG type="table" tableValues="{{ $table($mode, 1) }}" />
                <feFuncB type="table" tableValues="{{ $table($mode, 2) }}" />
            </feComponentTransfer>
        </filter>
    @endforeach
</svg>
