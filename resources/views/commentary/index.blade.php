<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-section-header>

        @if ($commentaries->total() === 0 && $activeTag === null)
            <x-empty-state class="mt-6" message="Essays and reflections will be published here in due course." />
        @else
            @if ($tags !== [])
                <x-filter-pills class="mt-12 border-y border-stone py-8" label="Topics" :options="$tags" :all-url="$clearUrl" :all-active="$activeTag === null" all-label="All topics" data-rise />
            @endif

            @if ($commentaries->total() === 0)
                <x-empty-state class="mt-6" message="Nothing has been written under this topic yet." />
            @else
                <ol class="mt-12 divide-y divide-stone border-y border-stone" data-rise-group>
                    @foreach ($commentaries as $commentary)
                        <li class="grid gap-3 py-10 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-10">
                            <div class="pt-2">
                                @if ($commentary->date)
                                    <x-date :date="$commentary->date" class="t-label text-bronze" />
                                @endif
                            </div>
                            <article class="flex flex-col gap-3">
                                <h2 class="font-display text-2xl font-light leading-snug text-evergreen sm:text-quote">
                                    <a href="{{ route('commentary.show', $commentary->slug) }}" class="text-evergreen">{{ $commentary->title }}</a>
                                </h2>
                                @if (filled($commentary->summary))
                                    <p class="t-body max-w-measure text-evergreen">{{ $commentary->summary }}</p>
                                @endif
                                @if (filled($commentary->tags))
                                    <ul class="mt-2 flex flex-wrap gap-2" aria-label="Topics">
                                        @foreach ($commentary->tags as $tag)
                                            <li><a href="{{ route('commentary', ['tag' => $tag]) }}" class="filter-pill min-h-9 px-3 text-xs">{{ $tag }}</a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </article>
                        </li>
                    @endforeach
                </ol>

                {{ $commentaries->links('components.pagination') }}
            @endif
        @endif
    </section>
</x-layouts.public>
