<x-layouts.public :title="$item->title" :description="$item->summary">
    <article class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <div class="mx-auto max-w-measure">
            <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$item->title" level="1" id="section-title" />

            <x-meta class="mt-8" :items="[$item->typeLabel(), $item->category, $item->date]" data-rise />

            @if (filled($item->summary))
                <p class="mt-10 font-display text-2xl font-light italic leading-snug text-ink-muted" data-rise>{{ $item->summary }}</p>
            @endif

            @if ($body !== '')
                <div class="prose-bio mt-10" data-rise>{!! $body !!}</div>
            @endif

            <nav class="mt-16" aria-label="Back" data-rise>
                <hr class="rule-brass" data-rule>
                <a href="{{ $section['url'] }}" class="btn-pill-outline mt-10 text-ink">All other information</a>
            </nav>
        </div>
    </article>
</x-layouts.public>
