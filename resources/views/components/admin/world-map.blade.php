{{--
    The analytics world map: every country outline inline (App\Support\WorldMap, no tiles, no
    requests), visited countries shaded in five tints of the line colour on a square-root scale.
    Each shaded country carries a <title> with its figure; the ranked country list beside it is the
    accessible alternative, so the drawing itself is one labelled image.
--}}
@props(['countries' => []])
@php
    $map = \App\Support\WorldMap::data();
    $visited = collect($countries)->keyBy('key');
@endphp
<figure {{ $attributes->class(['m-0']) }} data-world-map>
    <svg class="world-map" viewBox="{{ $map['viewBox'] }}" role="img" aria-label="{{ trans_choice('{0} World map of visitors by country, no countries shaded. The list of countries gives the figures.|{1} World map of visitors by country, :count country shaded. The list of countries gives the figures.|[2,*] World map of visitors by country, :count countries shaded. The list of countries gives the figures.', $visited->count()) }}">
        @foreach ($map['countries'] as $code => $country)
            @php $row = $visited->get($code); @endphp
            <path d="{{ $country['d'] }}" @if ($row) data-tier="{{ $row['tier'] }}" data-country="{{ $code }}" @endif>@if ($row)<title>{{ app()->isLocale(\App\Constants\Locales::DEFAULT) ? $country['name'] : \App\Support\Countries::name($code) }}: {{ trans_choice('{1} :count visitor|[2,*] :count visitors', $row['visitors'], ['count' => number_format($row['visitors'])]) }}</title>@endif</path>
        @endforeach
    </svg>
    <figcaption class="world-map-scale">
        <span>{{ __('Fewer') }}</span>
        <span class="world-map-scale__steps" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></span>
        <span>{{ __('More visitors') }}</span>
        <span class="ms-auto text-sm">{{ $map['attribution'] }}</span>
    </figcaption>
</figure>
