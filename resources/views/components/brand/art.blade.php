{{--
    One piece of logo artwork from App\Constants\BrandAssets as an <img>. `artwork` is a path
    pattern (BrandAssets::MARK, LOGO_HORIZONTAL, ...) with its viewBox `ratio`; `height` sets the
    width and height attributes (the CSS size comes from the caller's classes). `tone` follows
    <x-brand.mark>: "ivory" draws the on-dark colourway (evergreen bands), "evergreen" the page
    surface, so it draws both colourways and CSS shows the one for the current mode
    (.brand-art--light / --dark in resources/css/app.css). `wide` (with `wideRatio`) swaps in another
    artwork from the sm breakpoint up (the compact logo on phones, the horizontal one above).
    Pass `alt` to name the Professor; without it the image is decorative.
--}}
@props([
    'artwork',
    'ratio',
    'height',
    'tone' => \App\Constants\BrandMark::DEFAULT_TONE,
    'wide' => null,
    'wideRatio' => null,
    'alt' => null,
])
@php
    use App\Constants\BrandAssets;
    use App\Constants\BrandMark;

    $height = max(1, (int) $height);
    $colourways = $tone === BrandMark::TONE_EVERGREEN
        ? ['light' => BrandAssets::ON_LIGHT, 'dark' => BrandAssets::ON_DARK]
        : [null => BrandAssets::ON_DARK];
@endphp
@foreach ($colourways as $mode => $colourway)
    @php
        $classes = $attributes->class(['brand-art', 'brand-art--'.$mode => $mode !== '']);
        $src = asset(BrandAssets::path($artwork, $colourway));
        $img = sprintf(
            '<img src="%s" width="%d" height="%d" alt="%s" decoding="async" %s%s>',
            e($src),
            BrandAssets::widthFor($height, (float) $ratio),
            $height,
            e($alt ?? ''),
            $classes,
            $alt === null ? ' aria-hidden="true"' : '',
        );
    @endphp
    @if ($wide !== null)
        <picture class="contents"><source media="(min-width: 40rem)" srcset="{{ asset(BrandAssets::path($wide, $colourway)) }}" width="{{ BrandAssets::widthFor($height, (float) $wideRatio) }}" height="{{ $height }}">{!! $img !!}</picture>
    @else
        {!! $img !!}
    @endif
@endforeach
