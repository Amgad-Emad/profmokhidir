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
    <svg class="world-map" viewBox="{{ $map['viewBox'] }}" role="img" aria-label="World map of visitors by country, {{ $visited->count() }} {{ \Illuminate\Support\Str::plural('country', $visited->count()) }} shaded. The list of countries gives the figures.">
        @foreach ($map['countries'] as $code => $country)
            @php $row = $visited->get($code); @endphp
            <path d="{{ $country['d'] }}" @if ($row) data-tier="{{ $row['tier'] }}" data-country="{{ $code }}" @endif>@if ($row)<title>{{ $country['name'] }}: {{ number_format($row['visitors']) }} {{ \Illuminate\Support\Str::plural('visitor', $row['visitors']) }}</title>@endif</path>
        @endforeach
    </svg>
    <figcaption class="world-map-scale">
        <span>Fewer</span>
        <span class="world-map-scale__steps" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></span>
        <span>More visitors</span>
        <span class="ml-auto text-xs">{{ $map['attribution'] }}</span>
    </figcaption>
</figure>
