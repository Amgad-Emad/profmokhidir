<x-layouts.public :title="$commentary->title" :description="$commentary->summary" :type="\App\Constants\Seo::OG_TYPE_ARTICLE">
    <article class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <div class="mx-auto max-w-[68ch]">
            <x-section-header :label="$section['label']" :title="$commentary->title" level="1" id="section-title" />

            <x-meta class="mt-8" :items="[$commentary->byline, $commentary->date]" data-rise />

            @if (filled($commentary->summary))
                <p class="mt-10 font-display text-2xl font-light italic leading-snug text-ash" data-rise>{{ $commentary->summary }}</p>
            @endif

            @if ($body !== '')
                <div class="prose-bio mt-10" data-rise>{!! $body !!}</div>
            @endif

            @if (filled($commentary->external_url))
                <p class="mt-10" data-rise><x-external-link :href="$commentary->external_url" label="Read the original" /></p>
            @endif

            @if ($tags !== [])
                <nav class="mt-12 border-t border-stone pt-8" aria-label="Topics" data-rise>
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
                            <a href="{{ route('commentary.show', $previous->slug) }}" class="mt-3 block font-display text-xl font-light leading-snug text-evergreen">{{ $previous->title }}</a>
                        @endif
                    </div>
                    <div class="sm:text-right">
                        @if ($next)
                            <p class="t-label text-bronze">Next</p>
                            <a href="{{ route('commentary.show', $next->slug) }}" class="mt-3 block font-display text-xl font-light leading-snug text-evergreen">{{ $next->title }}</a>
                        @endif
                    </div>
                </div>
                <a href="{{ $section['url'] }}" class="btn-pill-outline mt-10 text-evergreen">All commentary</a>
            </nav>
        </div>
    </article>
</x-layouts.public>
