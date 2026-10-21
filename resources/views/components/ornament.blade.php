{{--
    A section break drawn in 1px brass hairlines (App\Constants\Ornaments): "rule-dot",
    "double-rule" or "fleuron". Strokes only, never a filled shape. `align` is "center"
    (default) or "start".
--}}
@props(['type' => \App\Constants\Ornaments::DEFAULT, 'align' => 'center'])
@php
    use App\Constants\Ornaments;

    $definitions = Ornaments::definitions();
    $type = array_key_exists($type, $definitions) ? $type : Ornaments::DEFAULT;
    $ornament = $definitions[$type];
@endphp
<div {{ $attributes->class(['ornament', 'ornament--'.$type, 'ornament--start' => $align === 'start']) }} role="separator" data-ornament="{{ $type }}" data-rise>
    <svg xmlns="http://www.w3.org/2000/svg" width="{{ $ornament['width'] }}" height="{{ $ornament['height'] }}" viewBox="0 0 {{ $ornament['width'] }} {{ $ornament['height'] }}" aria-hidden="true" focusable="false">
        @foreach ($ornament['shapes'] as $shape)
            @if ($shape['type'] === 'line')
                <line x1="{{ $shape['x1'] }}" y1="{{ $shape['y1'] }}" x2="{{ $shape['x2'] }}" y2="{{ $shape['y2'] }}" />
            @elseif ($shape['type'] === 'circle')
                <circle cx="{{ $shape['cx'] }}" cy="{{ $shape['cy'] }}" r="{{ $shape['r'] }}" />
            @else
                <path d="{{ $shape['d'] }}" />
            @endif
        @endforeach
    </svg>
</div>
