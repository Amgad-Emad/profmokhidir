{{-- Teaching materials as a syllabus: each course with its description and a numbered reading list. --}}
<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-chapter-opening>

        @if (! $hasAny)
            <x-empty-state class="mt-6" message="Teaching materials will appear here as they are prepared for sharing." />
        @else
            @if ($topics !== [] || count($types) > 1)
                <form novalidate method="get" action="{{ $clearUrl }}" class="mt-12 flex flex-col gap-6 border-y border-rule py-8 sm:flex-row sm:flex-wrap sm:items-end" data-rise>
                    @if ($topics !== [])
                        <x-form.select name="topic" id="filter-topic" label="Topic" :optional="false" size="compact" class="sm:min-w-56"
                                       placeholder="All topics" :value="$active['topic']" :options="array_combine($topics, $topics)" />
                    @endif
                    @if (count($types) > 1)
                        <x-form.select name="type" id="filter-type" label="Type" :optional="false" size="compact" class="sm:min-w-44"
                                       placeholder="All types" :value="$active['type']?->value" :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
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
                <p class="mt-8 max-w-2xl text-base font-light text-ink-muted" data-rise>Some materials are shared with members of the Scientific Community only. <a href="{{ $communityUrl }}" class="text-ink underline decoration-accent-line underline-offset-4">Apply to join the community</a> to download them.</p>
            @endif

            @if ($courses === [])
                <x-empty-state class="mt-6" message="No materials match these filters." />
            @endif

            <div class="syllabus">
                @foreach ($courses as $course)
                    <section class="syllabus__course" aria-labelledby="{{ $course['anchor'] }}">
                        <div class="syllabus__head" data-rise>
                            <h2 id="{{ $course['anchor'] }}" class="syllabus__title">@typeset($course['label'])</h2>
                            <span class="syllabus__count">{{ $course['materials']->count() }} {{ $course['materials']->count() === 1 ? 'item' : 'items' }}</span>
                        </div>
                        <hr class="rule-brass" data-rule>
                        @if (filled($course['description']))
                            <p class="syllabus__description" data-rise>@typeset($course['description'])</p>
                        @endif

                        <ol class="syllabus__list" data-rise-group>
                            @foreach ($course['materials'] as $material)
                                @php
                                    $id = (int) $material->getKey();
                                    $restricted = $material->isMembersOnly() && ! ($downloadable[$id] ?? false);
                                    $file = $material->file();
                                    $meta = [
                                        $material->type?->label(),
                                        $material->topic,
                                        filled($material->version) ? 'Version '.$material->version : null,
                                        $material->material_date,
                                        isset($files[$id]) ? $files[$id]['type'].' · '.$files[$id]['size'] : null,
                                    ];
                                @endphp
                                <li class="syllabus__item">
                                    <div class="syllabus__body">
                                        @if ($material->isMembersOnly())
                                            <p class="syllabus__lock"><x-icon name="lock" class="size-3.5" /><span>{{ $material->access->label() }}</span></p>
                                        @endif
                                        <h3 class="syllabus__item-title">@typeset($material->title)</h3>
                                        @if (filled($material->description))
                                            <p class="syllabus__item-description">@typeset($material->description)</p>
                                        @endif
                                        <x-meta class="mt-3" :items="$meta" />
                                        @if ($material->isMembersOnly())
                                            <p class="syllabus__members">{{ \App\Constants\TeachingAccess::MEMBERS_NOTE }}</p>
                                        @endif
                                    </div>
                                    <div class="syllabus__action">
                                        @if ($restricted)
                                            <a href="{{ $communityUrl }}" class="link-meta">Join the community<span class="sr-only"> to open {{ $material->title }}</span></a>
                                        @elseif ($file)
                                            <x-file-link :media="$file" :href="$downloadUrls[$id] ?? route('teaching.download', $material)" label="Download" />
                                        @elseif (filled($material->external_url))
                                            <x-external-link :href="$material->external_url" label="Open" />
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach
            </div>
        @endif

        <x-folio :section="$section['label']" position="bottom" class="mt-16" />
    </section>
</x-layouts.public>
