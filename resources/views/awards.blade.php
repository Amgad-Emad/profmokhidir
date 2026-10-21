<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-section-header>

        @if ($awards->isEmpty())
            <x-empty-state class="mt-6" message="Awards and recognitions will be listed here once they are published." />
        @else
            <ol class="timeline mt-16 ml-1 sm:ml-2" data-rise-group>
                @foreach ($awards as $award)
                    <li class="timeline__item pb-14 last:pb-0">
                        <article class="flex max-w-3xl flex-col gap-2">
                            @if ($award->year)
                                <p class="t-label text-bronze">{{ $award->year }}</p>
                            @endif
                            <h2 class="font-display text-2xl font-light leading-snug text-evergreen sm:text-quote">{{ $award->name }}</h2>
                            @if (filled($award->organisation))
                                <p class="font-display text-xl font-light italic text-ash">{{ $award->organisation }}</p>
                            @endif
                            @if (filled($award->description))
                                <p class="t-body mt-3 max-w-measure text-evergreen">{{ $award->description }}</p>
                            @endif
                            @if (filled($award->source_url))
                                <p class="mt-3"><x-external-link :href="$award->source_url" label="Source" /></p>
                            @endif
                        </article>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</x-layouts.public>
