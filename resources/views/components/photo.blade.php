{{--
    <x-photo :media="$portrait" tone="ivory" sizes="(min-width: 1024px) 40vw, 100vw" loading="eager" aspect="4/5" />
    Renders a <figure> with responsive WebP sources, the brand treatment, width/height and a label-style caption.
    :lightbox="true" wraps the frame in a link carrying the data the gallery lightbox reads.
    :label="true" sets the caption as a museum label; :lift="true" lets an archive duotone lift on hover.
--}}
<figure {{ $attributes->class(['photo flex flex-col gap-3']) }}>
    @if ($lightbox)
        <a
            href="{{ $src }}"
            class="photo__link block rounded-card"
            data-lightbox-item
            data-lightbox-src="{{ $src }}"
            @if ($srcset) data-lightbox-srcset="{{ $srcset }}" @endif
            data-lightbox-alt="{{ $alt }}"
            data-lightbox-treatment="{{ $treatmentClass }}"
            @if ($caption) data-lightbox-caption="{{ $caption }}" @endif
            @if ($placeYear) data-lightbox-meta="{{ $placeYear }}" @endif
            @if ($note) data-lightbox-note="{{ $note }}" @endif
            @if ($credit) data-lightbox-credit="{{ $credit }}" @endif
            @if ($width && $height) data-lightbox-width="{{ $width }}" data-lightbox-height="{{ $height }}" @endif
        >
    @endif
    <div @class(['photo__frame overflow-hidden rounded-card', 'photo__frame--lift' => $lifts(), 'bg-surface-raised/40' => $onDark(), 'bg-surface-sunken' => ! $onDark()]) @if ($aspect) style="aspect-ratio: {{ $aspect }}" @endif>
        @if ($lifts())
            {{-- The untreated print beneath; the duotone above fades a little on hover (desktop, motion allowed). --}}
            <img src="{{ $src }}" @if ($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif alt="" @if ($width && $height) width="{{ $width }}" height="{{ $height }}" @endif class="photo__under h-full w-full object-cover" loading="{{ $loading }}" decoding="async" aria-hidden="true">
        @endif
        <img
            src="{{ $src }}"
            @if ($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
            alt="{{ $alt }}"
            @if ($width && $height) width="{{ $width }}" height="{{ $height }}" @endif
            class="{{ $treatmentClass }} h-full w-full object-cover"
            loading="{{ $loading }}"
            decoding="async"
            @if ($loading === 'eager') fetchpriority="high" @endif
        >
    </div>
    @if ($lightbox)
            <span class="sr-only">View larger</span>
        </a>
    @endif
    @if ($label && $hasCaption())
        <x-museum-label :title="$showCaption ? $caption : null" :meta="$placeYear" :note="$note" :tone="$tone" />
    @elseif ($hasCaption())
        <figcaption class="flex flex-col gap-1">
            @if ($showCaption && $caption)
                <span class="text-base font-light {{ $onDark() ? 'text-ink' : 'text-ink-muted' }}">{{ $caption }}</span>
            @endif
            @if ($placeYear)
                <span class="t-label text-accent-text">{{ $placeYear }}</span>
            @endif
        </figcaption>
    @endif
</figure>
