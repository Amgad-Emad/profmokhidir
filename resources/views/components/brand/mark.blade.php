{{--
    The "MK" mark. `size` is the ring diameter in CSS pixels; clear space of one sixth
    of the diameter is added as padding on every side. Below 32px the ring is replaced
    by a solid disc (evergreen with ivory letters, inverted in the dark theme). `tone` is the ink of
    the letters: "ivory" for dark bands (default), "evergreen" for the page surface, where it follows
    the theme (evergreen on ivory, ivory on evergreen). The ring is always brass (--accent-line).
    Pass `decorative` when adjacent text already names the Professor (e.g. in a lockup).
--}}
@props([
    'size' => \App\Constants\BrandMark::DEFAULT_SIZE_PX,
    'tone' => \App\Constants\BrandMark::DEFAULT_TONE,
    'decorative' => false,
])
@php
    use App\Constants\BrandMark;

    $size = max(1, (int) $size);
    $tone = in_array($tone, BrandMark::tones(), true) ? $tone : BrandMark::DEFAULT_TONE;
    $disc = BrandMark::isDisc($size);
    $box = BrandMark::VIEWBOX;
    $centre = $box / 2;
    // The stroke is 1px on screen regardless of scale, so pull the ring in by half of
    // that (in viewBox units) to keep the hairline fully inside the box.
    $ringRadius = round($centre - (BrandMark::RING_STROKE_PX * $box / $size) / 2, 3);
    $inkToken = $tone === BrandMark::TONE_EVERGREEN ? '--ink-strong' : '--ink-on-band-strong';
    $titleId = 'brand-mark-title-'.uniqid();
@endphp
<svg {{ $attributes->class(['brand-mark', $disc ? 'brand-mark--disc' : 'brand-mark--ring']) }} xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $box }} {{ $box }}" style="padding: {{ BrandMark::clearSpace($size) }}px" data-size="{{ $size }}" data-tone="{{ $tone }}" data-variant="{{ $disc ? 'disc' : 'ring' }}"{!! $decorative ? ' aria-hidden="true" focusable="false"' : ' role="img" aria-labelledby="'.$titleId.'"' !!}>
    <title id="{{ $titleId }}">{{ __(BrandMark::TITLE) }}</title>
    @if ($disc)
        <circle cx="{{ $centre }}" cy="{{ $centre }}" r="{{ $centre }}" style="fill: var(--mark-disc)" />
    @else
        <circle cx="{{ $centre }}" cy="{{ $centre }}" r="{{ $ringRadius }}" fill="none" stroke-width="{{ BrandMark::RING_STROKE_PX }}" vector-effect="non-scaling-stroke" style="stroke: var(--accent-line)" />
    @endif
    <path d="{{ BrandMark::LETTERS_PATH }}" style="fill: var({{ $disc ? '--mark-disc-ink' : $inkToken }})" />
</svg>
