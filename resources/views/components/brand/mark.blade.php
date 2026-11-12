{{--
    The mark: the open book with the sprout and sunrays (App\Constants\BrandAssets::MARK). `size`
    is its height in CSS pixels; clear space of one sixth of that is added as padding on every
    side. Below 32px the rays break up, so the square evergreen app icon stands in for it. `tone`:
    "ivory" for dark bands (default, the on-dark colourway), "evergreen" for the page surface,
    where it follows the mode (on-light artwork on ivory, on-dark on evergreen). The artwork keeps
    its brand colours under every colour theme. Pass `decorative` when adjacent text already names
    the Professor (e.g. in a lockup).
--}}
@props([
    'size' => \App\Constants\BrandMark::DEFAULT_SIZE_PX,
    'tone' => \App\Constants\BrandMark::DEFAULT_TONE,
    'decorative' => false,
])
@php
    use App\Constants\BrandAssets;
    use App\Constants\BrandMark;

    $size = max(1, (int) $size);
    $tone = in_array($tone, BrandMark::tones(), true) ? $tone : BrandMark::DEFAULT_TONE;
    $icon = BrandMark::isDisc($size);
@endphp
<span {{ $attributes->class(['brand-mark', $icon ? 'brand-mark--icon' : 'brand-mark--full']) }} style="padding: {{ BrandMark::clearSpace($size) }}px; --brand-mark-height: {{ $size }}px" data-size="{{ $size }}" data-tone="{{ $tone }}" data-variant="{{ $icon ? 'icon' : 'full' }}"{!! $decorative ? ' aria-hidden="true"' : ' role="img" aria-label="'.e(__(BrandMark::TITLE)).'"' !!}>
    @if ($icon)
        <x-brand.art :artwork="BrandAssets::ICON" :ratio="BrandAssets::ICON_RATIO" :height="$size" tone="ivory" class="brand-mark__art" />
    @else
        <x-brand.art :artwork="BrandAssets::MARK" :ratio="BrandAssets::MARK_RATIO" :height="$size" :tone="$tone" class="brand-mark__art" />
    @endif
</span>
