{{-- Teaching materials as a syllabus: each course with its description and a numbered reading list. --}}
@inject('pageCopy', 'App\Services\PageContentService')
<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-chapter-opening>

        @if (! $hasAny)
            <x-empty-state class="mt-6" :message="$pageCopy->text('teaching', 'empty')" />
        @else
            @if ($topics !== [] || count($types) > 1)
                <form novalidate method="get" action="{{ $clearUrl }}" class="mt-12 flex flex-col gap-6 border-y border-rule py-8 sm:flex-row sm:flex-wrap sm:items-end" data-rise>
                    @if ($topics !== [])
                        <x-form.select name="topic" id="filter-topic" :label="__('Topic')" :optional="false" size="compact" class="sm:min-w-56"
                                       :placeholder="__('All topics')" :value="$active['topic']" :options="array_combine($topics, $topics)" />
                    @endif
                    @if (count($types) > 1)
                        <x-form.select name="type" id="filter-type" :label="__('Type')" :optional="false" size="compact" class="sm:min-w-44"
                                       :placeholder="__('All types')" :value="$active['type']?->value" :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
                    @endif
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="filter-pill">{{ __('Apply') }}</button>
                        @if ($isFiltered)
                            <a href="{{ $clearUrl }}" class="filter-pill">{{ __('Clear filters') }}</a>
                        @endif
                    </div>
                </form>
            @endif

            @if (! $canSeeMembers)
                <p class="mt-8 max-w-2xl text-base font-normal text-ink-muted" data-rise>{{ $pageCopy->text('teaching', 'members_note') }} <a href="{{ $communityUrl }}" class="text-ink underline decoration-accent-line underline-offset-4">{{ $pageCopy->text('teaching', 'members_link') }}</a> {{ $pageCopy->text('teaching', 'members_after') }}</p>
            @endif

            @if ($courses === [])
                <x-empty-state class="mt-6" :message="$pageCopy->text('teaching', 'empty_filtered')" />
            @endif

            <div class="syllabus">
                @foreach ($courses as $course)
                    <section class="syllabus__course" aria-labelledby="{{ $course['anchor'] }}">
                        <div class="syllabus__head" data-rise>
                            <h2 id="{{ $course['anchor'] }}" class="syllabus__title">@typeset($course['label'])</h2>
                            <span class="syllabus__count">{{ trans_choice('{1} :count item|[2,*] :count items', $course['materials']->count(), ['count' => app(\App\Services\LocaleService::class)->digits($course['materials']->count())]) }}</span>
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
                                        filled($material->version) ? __('Version :version', ['version' => $material->version]) : null,
                                        $material->material_date,
                                        isset($files[$id]) ? $files[$id]['type'].' · '.$files[$id]['size'] : null,
                                    ];
                                @endphp
                                <li class="syllabus__item">
                                    <div class="syllabus__body">
                                        @if ($material->isMembersOnly())
                                            <p class="syllabus__lock"><x-icon name="lock" class="size-3.5" /><span>{{ $material->access->label() }}</span></p>
                                        @endif
                                        <h3 class="syllabus__item-title" @contentLang($material)>@typeset($material->title)</h3>
                                        @if (filled($material->description))
                                            <p class="syllabus__item-description" @contentLang($material, 'description')>@typeset($material->description)</p>
                                        @endif
                                        <x-meta class="mt-3" :items="$meta" />
                                        @if ($material->isMembersOnly())
                                            <p class="syllabus__members">{{ __(\App\Constants\TeachingAccess::MEMBERS_NOTE) }}</p>
                                        @endif
                                    </div>
                                    <div class="syllabus__action">
                                        @if ($restricted)
                                            <a href="{{ $communityUrl }}" class="link-meta">{{ __('Join the community') }}<span class="sr-only"> {{ __('to open :title', ['title' => $material->title]) }}</span></a>
                                        @elseif ($file)
                                            <x-file-link :media="$file" :href="$downloadUrls[$id] ?? route('teaching.download', $material)" :label="__('Download')" />
                                        @elseif (filled($material->external_url))
                                            <x-external-link :href="$material->external_url" :label="__('Open')" />
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
