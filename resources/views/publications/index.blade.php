<x-layouts.public :title="$section['title']" :description="$section['summary']" :schema="$schema" :canonical="$clearUrl">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-section-header>

        @if ($publications->total() === 0 && ! $isFiltered)
            <x-empty-state class="mt-6" message="Publications will be listed here as they are catalogued." />
        @else
            {{-- Filters are plain links and a GET form, so they work without JavaScript. --}}
            <div class="mt-12 flex flex-col gap-8 border-y border-stone py-8 lg:flex-row lg:items-end lg:justify-between" data-rise>
                @if ($types !== [])
                    <x-filter-pills label="Type" :options="$types" :all-url="$allTypesUrl" :all-active="$active['type'] === null" all-label="All types" />
                @endif

                @if ($years !== [])
                    <form method="get" action="{{ $clearUrl }}" class="flex flex-wrap items-end gap-3">
                        @if ($active['type'])
                            <input type="hidden" name="type" value="{{ $active['type']->value }}">
                        @endif
                        <div class="flex flex-col gap-4">
                            <label for="filter-year" class="t-label text-bronze">Year</label>
                            <select id="filter-year" name="year" class="filter-select min-w-44">
                                <option value="">All years</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year['value'] }}"{!! $year['active'] ? ' selected' : '' !!}>{{ $year['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="filter-pill">Apply</button>
                        @if ($isFiltered)
                            <a href="{{ $clearUrl }}" class="filter-pill">Clear filters</a>
                        @endif
                    </form>
                @endif
            </div>

            <p class="t-label mt-8 text-bronze" role="status" data-rise>
                @if ($publications->total() === 0)
                    No publications match these filters
                @else
                    Showing {{ $publications->firstItem() }}–{{ $publications->lastItem() }} of {{ $publications->total() }}
                    @if ($active['type']) · {{ $active['type']->label() }} @endif
                    @if ($active['year']) · {{ $active['year'] }} @endif
                @endif
            </p>

            @if ($publications->total() === 0)
                <x-empty-state class="mt-2" message="Nothing has been published under this type or year yet." />
            @endif

            <div class="mt-12 flex flex-col gap-16">
                @foreach ($groups as $group)
                    <section aria-labelledby="year-{{ $group['year'] ?? 'undated' }}">
                        <h2 id="year-{{ $group['year'] ?? 'undated' }}" class="font-display text-quote font-light leading-tight" data-rise>{{ $group['label'] }}</h2>
                        <ol class="mt-6 divide-y divide-stone border-y border-stone" data-rise-group>
                            @foreach ($group['items'] as $publication)
                                @php $file = $publication->file(); @endphp
                                <li class="flex flex-col gap-3 py-8">
                                    <x-meta :items="[$publication->type?->label(), $publication->year]" />
                                    <h3 class="font-display text-2xl font-light leading-snug text-evergreen">{{ $publication->title }}</h3>
                                    @if (filled($publication->authors))
                                        <p class="t-body text-evergreen">{{ $publication->authors }}</p>
                                    @endif
                                    @if (filled($publication->venue_publisher))
                                        <p class="font-display text-xl font-light italic text-ash">{{ $publication->venue_publisher }}</p>
                                    @endif

                                    @if ($publication->doiUrl() || filled($publication->url) || $file)
                                        <ul class="mt-2 flex flex-wrap gap-x-8 gap-y-3" aria-label="Links for {{ $publication->title }}">
                                            @if ($publication->doiUrl())
                                                <li><a href="{{ $publication->doiUrl() }}" rel="noopener" class="link-meta">DOI · {{ $publication->doi }}</a></li>
                                            @endif
                                            @if (filled($publication->url))
                                                <li><x-external-link :href="$publication->url" label="Read online" /></li>
                                            @endif
                                            @if ($file)
                                                <li><x-file-link :media="$file" :href="$file->getUrl()" label="Download" /></li>
                                            @endif
                                        </ul>
                                    @endif

                                    @if (filled($publication->citation))
                                        <details class="cite mt-2" x-data="{ copied: false }">
                                            <summary class="link-meta">Cite</summary>
                                            <p class="cite__text" x-ref="citation">{{ $publication->citation }}</p>
                                            <button type="button" class="filter-pill mt-3" x-cloak @click="navigator.clipboard.writeText($refs.citation.textContent.trim()).then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
                                                <span x-text="copied ? 'Copied' : 'Copy citation'">Copy citation</span>
                                            </button>
                                            <span class="sr-only" role="status" x-text="copied ? 'Citation copied to the clipboard' : ''"></span>
                                        </details>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach
            </div>

            {{ $publications->links('components.pagination') }}
        @endif
    </section>
</x-layouts.public>
