<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-section-header>

        @if (! $hasAny)
            <x-empty-state class="mt-6" message="Teaching materials will appear here as they are prepared for sharing." />
        @else
            @if ($topics !== [] || count($types) > 1)
                <form method="get" action="{{ $clearUrl }}" class="mt-12 flex flex-col gap-6 border-y border-stone py-8 sm:flex-row sm:flex-wrap sm:items-end" data-rise>
                    @if ($topics !== [])
                        <div class="flex flex-col gap-4">
                            <label for="filter-topic" class="t-label text-bronze">Topic</label>
                            <select id="filter-topic" name="topic" class="filter-select min-w-56">
                                <option value="">All topics</option>
                                @foreach ($topics as $topic)
                                    <option value="{{ $topic }}"{!! $active['topic'] === $topic ? ' selected' : '' !!}>{{ $topic }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if (count($types) > 1)
                        <div class="flex flex-col gap-4">
                            <label for="filter-type" class="t-label text-bronze">Type</label>
                            <select id="filter-type" name="type" class="filter-select min-w-44">
                                <option value="">All types</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->value }}"{!! $active['type'] === $type ? ' selected' : '' !!}>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="filter-pill">Apply</button>
                        @if ($isFiltered)
                            <a href="{{ $clearUrl }}" class="filter-pill">Clear filters</a>
                        @endif
                    </div>
                </form>
            @endif

            @if (! $canSeeMembers)
                <p class="mt-8 max-w-2xl text-base font-light text-ash" data-rise>Some materials are shared with members of the Scientific Community only. <a href="{{ $communityUrl }}" class="text-evergreen underline decoration-brass underline-offset-4">Apply to join the community</a> to download them.</p>
            @endif

            @if ($courses === [])
                <x-empty-state class="mt-6" message="No materials match these filters." />
            @endif

            <div class="mt-14 flex flex-col gap-20">
                @foreach ($courses as $course => $materials)
                    @php $anchor = 'course-'.($course === '' ? 'other' : \Illuminate\Support\Str::slug($course)); @endphp
                    <section aria-labelledby="{{ $anchor }}">
                        <h2 id="{{ $anchor }}" class="font-display text-quote font-light leading-tight" data-rise>{{ $course === '' ? 'Other materials' : $course }}</h2>
                        <ul class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-rise-group>
                            @foreach ($materials as $material)
                                @php
                                    $file = $material->file();
                                    $restricted = $material->isMembersOnly() && ! ($downloadable[$material->getKey()] ?? false);
                                @endphp
                                <li class="card flex h-full flex-col gap-3">
                                    <x-meta :items="[$material->type?->label(), $material->topic, $material->isMembersOnly() ? $material->access->label() : null]" />
                                    <h3 class="font-display text-2xl font-light leading-snug text-evergreen">{{ $material->title }}</h3>
                                    @if (filled($material->description))
                                        <p class="text-base font-light text-evergreen">{{ $material->description }}</p>
                                    @endif
                                    @if (filled($material->version) || $material->material_date)
                                        <p class="text-sm font-light text-ash">
                                            @if (filled($material->version)){{ $material->version }}@endif
                                            @if (filled($material->version) && $material->material_date) · @endif
                                            <x-date :date="$material->material_date" />
                                        </p>
                                    @endif
                                    <div class="mt-auto pt-3">
                                        @if ($restricted)
                                            <p class="text-sm font-light italic text-ash">For community members. <a href="{{ $communityUrl }}" class="not-italic text-evergreen underline decoration-brass underline-offset-4">Join the community</a></p>
                                        @elseif ($file)
                                            <x-file-link :media="$file" :href="route('teaching.download', $material)" label="Download" />
                                        @elseif (filled($material->external_url))
                                            <x-external-link :href="$material->external_url" label="Open" />
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.public>
