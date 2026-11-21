{{--
    The publications catalogue, in the manner of a library catalogue: a "Selected works" strip, then
    filters (type, year range, topic) beside the records, which are grouped by decade. The filters are a
    plain GET form, so they work without JavaScript; on small screens they move into a sheet.
--}}
@inject('terms', 'App\Services\TermTranslationService')
@inject('pageCopy', 'App\Services\PageContentService')
@inject('typesetter', 'App\Services\TypesetterService')
@inject('localeService', 'App\Services\LocaleService')
<x-layouts.public :title="$section['title']" :description="$section['summary']" :schema="$schema" :canonical="$clearUrl">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-chapter-opening>

        @if ($publications->total() === 0 && ! $isFiltered)
            <x-empty-state class="mt-6" :message="$pageCopy->text('publications', 'empty')" />
        @else
            @if ($featured->isNotEmpty())
                <section class="selected-works mt-12 lg:mt-16" aria-labelledby="selected-works-title">
                    <h2 id="selected-works-title" class="t-label text-accent-text" data-rise>{{ $pageCopy->text('publications', 'selected_label') }}</h2>
                    <hr class="rule-brass mt-4" data-rule>
                    <ol class="selected-works__list" data-rise-group>
                        @foreach ($featured as $work)
                            <li class="selected-works__item">
                                <x-meta :items="[$work->type?->label()]" />
                                <p @contentLang($work) class="selected-works__title">@typeset($work->title)</p>
                                @if (filled($work->venue_publisher))
                                    <p class="selected-works__venue">@typeset($work->venue_publisher)</p>
                                @endif
                                @if ($work->year)
                                    <p class="selected-works__year">{{ $work->year }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            <div class="catalogue mt-12 lg:mt-14" x-data="catalogueSheet">
                <div class="catalogue__toolbar lg:hidden" x-cloak>
                    <x-button type="button" x-ref="toggle" aria-controls="catalogue-filters" x-bind:aria-expanded="open.toString()" x-on:click="show()">
                        {{ $pageCopy->text('publications', 'refine_label') }}
                        @if ($activeCount > 0)
                            <span class="catalogue__badge">@num($activeCount)<span class="sr-only"> {{ __('active') }}</span></span>
                        @endif
                    </x-button>
                </div>

                <div class="catalogue__backdrop lg:hidden" x-cloak x-show="open" x-on:click="close()" aria-hidden="true"></div>

                <aside id="catalogue-filters" class="catalogue__filters" aria-labelledby="catalogue-filters-title"
                    x-ref="panel" tabindex="-1"
                    x-bind:class="{ 'is-sheet': enhanced, 'is-open': open }"
                    x-bind:role="open ? 'dialog' : null" x-bind:aria-modal="open ? 'true' : null"
                    x-on:keydown.escape="open && close()" x-on:keydown.tab="trap($event)">
                    <div class="catalogue__filters-head">
                        <h2 id="catalogue-filters-title" class="catalogue__heading">{{ __('Refine') }}</h2>
                        <button type="button" class="catalogue__close lg:hidden" x-cloak x-ref="close" x-on:click="close()">
                            <x-icon name="close" class="size-5" /><span class="sr-only">{{ __('Close the filters') }}</span>
                        </button>
                    </div>

                    <form novalidate method="get" action="{{ $clearUrl }}" class="catalogue__form">
                        @if ($types !== [])
                            <x-form.radio-group name="type" :label="__('Type')" :optional="false" :value="$active['type']?->value ?? ''"
                                                :options="['' => __('All types')] + collect($types)->mapWithKeys(fn ($type) => [$type['value'] => $type['label']])->all()" />
                        @endif

                        @if ($years !== [])
                            <fieldset class="form-fieldset">
                                <legend class="t-label form-label">{{ __('Years') }}</legend>
                                <div class="catalogue__range">
                                    <x-form.select name="from" id="filter-from" :label="__('From')" :optional="false" size="compact" :value="$active['from']"
                                                   :placeholder="__('Earliest')" :options="array_combine(array_reverse($years), array_reverse($years))" />
                                    <x-form.select name="to" id="filter-to" :label="__('To')" :optional="false" size="compact" :value="$active['to']"
                                                   :placeholder="__('Latest')" :options="array_combine($years, $years)" />
                                </div>
                            </fieldset>
                        @endif

                        @if ($topics !== [])
                            <x-form.radio-group name="topic" :label="__('Topic')" :optional="false" :value="$active['topic'] ?? ''"
                                                :options="['' => __('All topics')] + $terms->options(\App\Constants\TermVocabulary::PublicationTopic, $topics)" />
                        @endif

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="filter-pill">{{ __('Apply') }}</button>
                            @if ($isFiltered)
                                <a href="{{ $clearUrl }}" class="filter-pill">{{ __('Clear filters') }}</a>
                            @endif
                        </div>
                    </form>
                </aside>

                <div class="catalogue__results">
                    <p class="catalogue__count" role="status">
                        @if ($publications->total() === 0)
                            {{ $pageCopy->text('publications', 'none_match') }}
                        @else
                            {{ trans_choice('{1} Showing :first–:last of :count record|[2,*] Showing :first–:last of :count records', $publications->total(), ['first' => $localeService->digits($publications->firstItem()), 'last' => $localeService->digits($publications->lastItem()), 'count' => $localeService->digits($publications->total())]) }}@foreach ($activeSummary as $part) · {{ $part }}@endforeach
                        @endif
                    </p>

                    @if ($publications->total() === 0)
                        <x-empty-state class="mt-2" :message="$pageCopy->text('publications', 'empty_filtered')" />
                    @endif

                    @foreach ($groups as $group)
                        <section class="catalogue__decade" aria-labelledby="{{ $group['anchor'] }}">
                            <div class="catalogue__decade-head" data-rise>
                                <h2 id="{{ $group['anchor'] }}" class="catalogue__decade-title">{{ $group['label'] }}</h2>
                                <span class="catalogue__decade-count">{{ trans_choice('{1} :count record|[2,*] :count records', $group['count'], ['count' => $localeService->digits($group['count'])]) }}</span>
                            </div>
                            <hr class="rule-brass" data-rule>

                            <ol class="catalogue__records" data-rise-group>
                                @foreach ($group['items'] as $publication)
                                    @php
                                        $file = $publication->file();
                                        $id = (int) $publication->getKey();
                                    @endphp
                                    <li class="record" id="publication-{{ $publication->slug }}">
                                        <p class="record__year" aria-hidden="true">{{ $publication->year }}</p>
                                        <article class="record__body" aria-labelledby="publication-{{ $id }}-title">
                                            <x-meta :items="[$publication->type?->label(), $terms->label(\App\Constants\TermVocabulary::PublicationTopic, $publication->topic)]" />
                                            <h3 @contentLang($publication) id="publication-{{ $id }}-title" class="record__title">@typeset($publication->title)</h3>
                                            @if (filled($publication->authors))
                                                <p class="record__authors">{{ $authors[$id] }}</p>
                                            @endif
                                            @if (filled($publication->venue_publisher) || $publication->year)
                                                @php
                                                    // Venue in italics, then ", year" (the comma hidden from screen readers), with no space before the comma.
                                                    $venue = filled($publication->venue_publisher) ? '<i>'.$typesetter->inline($publication->venue_publisher).'</i>' : '';
                                                    $year = $publication->year ? '<span class="record__venue-year">'.e($publication->year).'</span>' : '';
                                                @endphp
                                                <p class="record__venue">{!! $venue.($venue !== '' && $year !== '' ? '<span aria-hidden="true">,</span> ' : '').$year !!}</p>
                                            @endif

                                            @if ($publication->doiUrl() || filled($publication->url) || $file)
                                            <ul class="record__links" aria-label="{{ __('Links for :title', ['title' => $publication->title]) }}">
                                                @if ($publication->doiUrl())
                                                    <li><a href="{{ $publication->doiUrl() }}" rel="noopener" class="record__doi"><span class="record__doi-label">DOI</span> <span class="font-mono">{{ $publication->doi }}</span></a></li>
                                                @endif
                                                @if (filled($publication->url))
                                                    <li><x-external-link :href="$publication->url" :label="__('Read online')" /></li>
                                                @endif
                                                @if ($file)
                                                    <li><x-file-link :media="$file" :href="$file->getUrl()" :label="__('Download')" /></li>
                                                @endif
                                            </ul>
                                            @endif
                                        </article>
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endforeach

                    {{ $publications->links('components.pagination') }}
                </div>
            </div>
        @endif

        <x-folio :section="$section['label']" position="bottom" class="mt-16" />
    </section>
</x-layouts.public>
