<x-layouts.public :title="$commentary->title" :description="$commentary->summary" :type="\App\Constants\Seo::OG_TYPE_ARTICLE">
    <article class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <div class="sidenote-host mx-auto">
            <x-folio :section="$section['label']" class="mb-16" />

            <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$commentary->title" level="1" id="section-title" />

            @if (filled($commentary->byline) || $commentary->date)
                <p class="mt-8 font-display text-xl font-light leading-8 text-ash" data-rise>
                    @if (filled($commentary->byline))
                        <span class="smallcaps">@typeset($commentary->byline)</span>
                    @endif
                    @if (filled($commentary->byline) && $commentary->date)
                        <span aria-hidden="true">{{ \App\Constants\DateFormats::META_SEPARATOR }}</span>
                    @endif
                    <x-date :date="$commentary->date" class="whitespace-nowrap" />@if (filled($commentary->external_url))<x-sidenote :number="1">First published at {{ parse_url($commentary->external_url, PHP_URL_HOST) ?: $commentary->external_url }}; the original is linked at the end.</x-sidenote>@endif
                </p>
            @endif

            @if (filled($commentary->summary))
                <p class="mt-10 font-display text-2xl font-light italic leading-10 text-ash" data-rise>@typeset($commentary->summary)</p>
            @endif

            @if ($body !== '')
                <x-drop-cap class="prose-bio mt-10" data-rise>{!! $body !!}</x-drop-cap>
            @endif

            @if (filled($commentary->external_url))
                <p class="mt-10" data-rise><x-external-link :href="$commentary->external_url" label="Read the original" /></p>
            @endif

            <x-ornament type="fleuron" />

            @if ($tags !== [])
                <nav aria-label="Topics" data-rise>
                    <p class="t-label text-bronze">Topics</p>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($tags as $tag)
                            <li><a href="{{ $tag['url'] }}" class="filter-pill">{{ $tag['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            <nav class="mt-16" aria-label="More commentary" data-rise>
                <hr class="rule-brass" data-rule>
                <div class="mt-8 grid gap-8 sm:grid-cols-2">
                    <div>
                        @if ($previous)
                            <p class="t-label text-bronze">Previous</p>
                            <a href="{{ route('commentary.show', $previous->slug) }}" class="mt-3 block font-display text-xl font-light leading-snug text-evergreen">@typeset($previous->title)</a>
                        @endif
                    </div>
                    <div class="sm:text-right">
                        @if ($next)
                            <p class="t-label text-bronze">Next</p>
                            <a href="{{ route('commentary.show', $next->slug) }}" class="mt-3 block font-display text-xl font-light leading-snug text-evergreen">@typeset($next->title)</a>
                        @endif
                    </div>
                </div>
                <a href="{{ $section['url'] }}" class="btn-pill-outline mt-10 text-evergreen">All commentary</a>
            </nav>

            <x-seal />

            <x-folio :section="$section['label']" position="bottom" class="mt-16" />
        </div>
    </article>
</x-layouts.public>
