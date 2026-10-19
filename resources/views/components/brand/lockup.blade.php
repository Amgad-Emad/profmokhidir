{{--
    Mark with the name. `variant`: "stacked" (mark above the text; footer, hero) or
    "horizontal" (mark left of the text; nav). `tone` follows the mark: "ivory" on dark
    surfaces (label in brass), "evergreen" on light surfaces (label in bronze).
    On the translucent glass header pass `label-tone="champagne"`: brass falls below
    4.5:1 once ivory content shows through. The name inherits the current text colour.
    Pass `href` to render it as a link.
--}}
@props([
    'variant' => 'horizontal',
    'tone' => \App\Constants\BrandMark::DEFAULT_TONE,
    'labelTone' => null,
    'href' => null,
    'size' => null,
])
@php
    use App\Constants\BrandMark;

    $stacked = $variant === 'stacked';
    $size ??= $stacked ? 72 : BrandMark::DISC_BELOW_PX;
    $labelColour = match ($labelTone) {
        'champagne' => 'text-champagne',
        'bronze' => 'text-bronze',
        'brass' => 'text-brass',
        default => $tone === BrandMark::TONE_EVERGREEN ? 'text-bronze' : 'text-brass',
    };
    $tag = $href ? 'a' : 'span';
@endphp
@php
    $classes = $attributes->class([
        'brand-lockup inline-flex no-underline hover:no-underline text-current',
        'brand-lockup--stacked flex-col items-center text-center' => $stacked,
        'brand-lockup--horizontal items-center gap-2' => ! $stacked,
    ]);
@endphp
<{{ $tag }}{!! $href ? ' href="'.e($href).'"' : '' !!} {{ $classes }} data-variant="{{ $variant }}">
    <x-brand.mark :size="$size" :tone="$tone" decorative />
    <span @class(['flex flex-col', 'mt-4 items-center gap-2' => $stacked, 'items-start gap-0.5' => ! $stacked])>
        <span class="t-label {{ $labelColour }}">Professor</span>
        <span @class(['font-display font-light leading-none', 'text-3xl' => $stacked, 'text-lg sm:text-xl' => ! $stacked])>Mohamed Osman Khidir</span>
    </span>
</{{ $tag }}>
