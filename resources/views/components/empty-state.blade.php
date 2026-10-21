{{--
    Quiet notice for a list with nothing published, set as an epigraph: a short brass rule,
    the line in italic Cormorant and an optional attribution in small caps. `tone` is the
    ink: "evergreen" on ivory surfaces (default) or "ivory" on evergreen surfaces.
--}}
@props(['message', 'attribution' => null, 'tone' => 'evergreen'])
<figure {{ $attributes->class(['empty-state flex flex-col items-start gap-5 py-12']) }}>
    <hr class="rule-brass-short" data-rule>
    <blockquote class="empty-state__quote">
        <p class="font-display text-2xl font-light italic {{ $tone === 'ivory' ? 'text-champagne' : 'text-ash' }}">{{ $message }}</p>
    </blockquote>
    @if (filled($attribution))
        <figcaption class="empty-state__attribution smallcaps {{ $tone === 'ivory' ? 'text-champagne' : 'text-ash' }}"><span aria-hidden="true">— </span>{{ $attribution }}</figcaption>
    @endif
</figure>
