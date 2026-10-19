{{--
    <x-photo :media="$portrait" tone="ivory" sizes="(min-width: 1024px) 40vw, 100vw" loading="eager" aspect="4/5" />
    Renders a <figure> with responsive WebP sources, the brand treatment and a label-style caption.
--}}
<figure {{ $attributes->class(['photo flex flex-col gap-3']) }}>
    <div @class(['photo__frame overflow-hidden rounded-card', 'bg-cypress/40' => $onDark(), 'bg-stone' => ! $onDark()]) @if ($aspect) style="aspect-ratio: {{ $aspect }}" @endif>
        <img
            src="{{ $src }}"
            @if ($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
            alt="{{ $alt }}"
            class="{{ $treatmentClass }} h-full w-full object-cover"
            loading="{{ $loading }}"
            decoding="async"
            @if ($loading === 'eager') fetchpriority="high" @endif
        >
    </div>
    @if ($hasCaption())
        <figcaption class="flex flex-col gap-1">
            @if ($showCaption && $caption)
                <span class="text-base font-light {{ $onDark() ? 'text-champagne' : 'text-ash' }}">{{ $caption }}</span>
            @endif
            @if ($placeYear)
                <span class="t-label {{ $onDark() ? 'text-brass' : 'text-bronze' }}">{{ $placeYear }}</span>
            @endif
        </figcaption>
    @endif
</figure>
