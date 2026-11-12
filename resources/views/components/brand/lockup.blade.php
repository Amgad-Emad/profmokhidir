{{--
    The logo with the name. `variant`: "stacked" (mark above the name; footer, sign-in) or
    "horizontal" (mark left of the name; nav). On English pages it is the logo artwork from
    App\Constants\BrandAssets, whose name is set in outlined capitals: the horizontal variant
    draws the compact artwork (name on two lines) on phones and the one-line artwork from sm up;
    `compact` keeps the compact artwork at every width (narrow bars: the admin sidebar, the chat
    bar). The stacked variant omits the tagline unless `tagline` is passed (pages that set the
    tagline as live text nearby). The artwork's name is Latin, so on Arabic pages the mark stands
    beside the translated name set as live text.
    `tone` follows the mark: "ivory" on dark bands, "evergreen" on the page surface (follows the
    mode). The Arabic label takes the accent text token; on the translucent glass header pass
    `label-tone="champagne"` so it uses the body ink instead (brass falls below 4.5:1 once ivory
    content shows through). Pass `href` to render it as a link.
--}}
@props([
    'variant' => 'horizontal',
    'tone' => \App\Constants\BrandMark::DEFAULT_TONE,
    'labelTone' => null,
    'href' => null,
    'size' => null,
    'compact' => false,
    'tagline' => false,
])
@php
    use App\Constants\BrandAssets;
    use App\Constants\BrandMark;
    use App\Constants\Locales;

    $stacked = $variant === 'stacked';
    $artwork = app()->getLocale() === Locales::EN;
    $labelColour = $labelTone === 'champagne' ? 'text-ink' : 'text-accent-text';
    $tag = $href ? 'a' : 'span';
    $name = __(BrandMark::TITLE);

    $classes = $attributes->class([
        'brand-lockup inline-flex no-underline hover:no-underline text-current',
        'brand-lockup--stacked flex-col items-center text-center' => $stacked,
        'brand-lockup--horizontal items-center gap-2' => ! $stacked,
        'brand-lockup--compact' => $compact,
        'brand-lockup--artwork' => $artwork,
    ]);
@endphp
<{{ $tag }}{!! $href ? ' href="'.e($href).'"' : '' !!} {{ $classes }} data-variant="{{ $variant }}">
    @if ($artwork)
        @if ($stacked)
            <x-brand.art :artwork="$tagline ? BrandAssets::LOGO_STACKED : BrandAssets::LOGO_STACKED_NO_TAGLINE" :ratio="$tagline ? BrandAssets::LOGO_STACKED_RATIO : BrandAssets::LOGO_STACKED_NO_TAGLINE_RATIO" :height="(int) ($size ?? 171)" :tone="$tone" :alt="$name" class="brand-lockup__art brand-lockup__art--stacked" />
        @elseif ($compact)
            <x-brand.art :artwork="BrandAssets::LOGO_COMPACT" :ratio="BrandAssets::LOGO_COMPACT_RATIO" :height="(int) ($size ?? 40)" :tone="$tone" :alt="$name" class="brand-lockup__art brand-lockup__art--compact" />
        @else
            <x-brand.art :artwork="BrandAssets::LOGO_COMPACT" :ratio="BrandAssets::LOGO_COMPACT_RATIO" :wide="BrandAssets::LOGO_HORIZONTAL" :wide-ratio="BrandAssets::LOGO_HORIZONTAL_RATIO" :height="(int) ($size ?? 48)" :tone="$tone" :alt="$name" class="brand-lockup__art brand-lockup__art--horizontal" />
        @endif
    @else
        <x-brand.mark :size="$size ?? ($stacked ? 72 : 36)" :tone="$tone" decorative />
        <span @class(['flex flex-col', 'mt-4 items-center gap-2' => $stacked, 'items-start gap-0.5' => ! $stacked])>
            <span class="t-label {{ $labelColour }}">{{ __('Professor') }}</span>
            <span @class(['font-display font-light leading-none', 'text-3xl' => $stacked, 'text-lg sm:text-xl' => ! $stacked && ! $compact, 'text-lg whitespace-nowrap' => ! $stacked && $compact])>{{ __('Mohamed Osman Khidir') }}</span>
        </span>
    @endif
</{{ $tag }}>
