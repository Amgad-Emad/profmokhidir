{{--
    A short italic quotation for a chapter opening, set to the right on a narrow measure,
    with an optional attribution in small caps. `tone`: "evergreen" on ivory (default) or
    "ivory" on evergreen surfaces.
--}}
@props(['attribution' => null, 'tone' => 'evergreen'])
<figure {{ $attributes->class(['epigraph', $tone === 'ivory' ? 'text-ink' : 'text-ink-muted']) }}>
    <blockquote class="epigraph__quote"><p>{{ $slot }}</p></blockquote>
    @if (filled($attribution))
        <figcaption class="epigraph__attribution"><span aria-hidden="true">— </span><span class="smallcaps">@typeset($attribution)</span></figcaption>
    @endif
</figure>
