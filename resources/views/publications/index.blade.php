{{--
    The publications catalogue, in the manner of a library catalogue: a "Selected works" strip, then
    filters (type, year range, topic) beside the records, which are grouped by decade. The filters are a
    plain GET form, so they work without JavaScript; on small screens they move into a sheet.
--}}
@inject('typesetter', 'App\Services\TypesetterService')
<x-layouts.public :title="$section['title']" :description="$section['summary']" :schema="$schema" :canonical="$clearUrl">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-chapter-opening>

        @if ($publications->total() === 0 && ! $isFiltered)
            <x-empty-state class="mt-6" message="Publications will be listed here as they are catalogued." />
        @else
            @if ($featured->isNotEmpty())
                <section class="selected-works mt-20" aria-labelledby="selected-works-title">
                    <h2 id="selected-works-title" class="t-label text-bronze" data-rise>{{ \App\Constants\Catalogue::SELECTED_WORKS_LABEL }}</h2>
                    <hr class="rule-brass mt-4" data-rule>
                    <ol class="selected-works__list" data-rise-group>
                        @foreach ($featured as $work)
                            <li class="selected-works__item">
                                <x-meta :items="[$work->type?->label()]" />
                                <p class="selected-works__title">@typeset($work->title)</p>
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

            <div class="catalogue mt-20" x-data="catalogueSheet">
                <div class="catalogue__toolbar lg:hidden" x-cloak>
                    <button type="button" class="btn-pill-outline text-evergreen" x-ref="toggle" aria-controls="catalogue-filters" x-bind:aria-expanded="open.toString()" x-on:click="show()">
                        Refine the catalogue
                        @if ($activeCount > 0)
                            <span class="catalogue__badge">{{ $activeCount }}<span class="sr-only"> active</span></span>
                        @endif
                    </button>
                </div>

                <div class="catalogue__backdrop lg:hidden" x-cloak x-show="open" x-on:click="close()" aria-hidden="true"></div>

                <aside id="catalogue-filters" class="catalogue__filters" aria-labelledby="catalogue-filters-title"
                    x-ref="panel" tabindex="-1"
                    x-bind:class="{ 'is-sheet': enhanced, 'is-open': open }"
                    x-bind:role="open ? 'dialog' : null" x-bind:aria-modal="open ? 'true' : null"
                    x-on:keydown.escape="open && close()" x-on:keydown.tab="trap($event)">
                    <div class="catalogue__filters-head">
                        <h2 id="catalogue-filters-title" class="catalogue__heading">Refine</h2>
                        <button type="button" class="catalogue__close lg:hidden" x-cloak x-ref="close" x-on:click="close()">
                            <x-icon name="close" class="size-5" /><span class="sr-only">Close the filters</span>
                        </button>
                    </div>

                    <form method="get" action="{{ $clearUrl }}" class="catalogue__form">
                        @if ($types !== [])
                            <fieldset>
                                <legend class="catalogue__legend">Type</legend>
                                <ul class="catalogue__options">
                                    <li><label class="catalogue__option"><input type="radio" name="type" value="" @checked($active['type'] === null)> <span>All types</span></label></li>
                                    @foreach ($types as $type)
                                        <li><label class="catalogue__option"><input type="radio" name="type" value="{{ $type['value'] }}" @checked($type['active'])> <span>{{ $type['label'] }}</span></label></li>
                                    @endforeach
                                </ul>
                            </fieldset>
                        @endif

                        @if ($years !== [])
                            <fieldset>
                                <legend class="catalogue__legend">Years</legend>
                                <div class="catalogue__range">
                                    <div>
                                        <label for="filter-from" class="catalogue__sublabel">From</label>
                                        <select id="filter-from" name="from" class="filter-select">
                                            <option value="">Earliest</option>
                                            @foreach (array_reverse($years) as $year)
                                                <option value="{{ $year }}" @selected($active['from'] === $year)>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="filter-to" class="catalogue__sublabel">To</label>
                                        <select id="filter-to" name="to" class="filter-select">
                                            <option value="">Latest</option>
                                            @foreach ($years as $year)
                                                <option value="{{ $year }}" @selected($active['to'] === $year)>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </fieldset>
                        @endif

                        @if ($topics !== [])
                            <fieldset>
                                <legend class="catalogue__legend">Topic</legend>
                                <ul class="catalogue__options">
                                    <li><label class="catalogue__option"><input type="radio" name="topic" value="" @checked($active['topic'] === null)> <span>All topics</span></label></li>
                                    @foreach ($topics as $topic)
                                        <li><label class="catalogue__option"><input type="radio" name="topic" value="{{ $topic }}" @checked($active['topic'] === $topic)> <span>{{ $topic }}</span></label></li>
                                    @endforeach
                                </ul>
                            </fieldset>
                        @endif

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="filter-pill">Apply</button>
                            @if ($isFiltered)
                                <a href="{{ $clearUrl }}" class="filter-pill">Clear filters</a>
                            @endif
                        </div>
                    </form>
                </aside>

                <div class="catalogue__results">
                    <p class="catalogue__count" role="status">
                        @if ($publications->total() === 0)
                            No publications match these filters
                        @else
                            Showing {{ $publications->firstItem() }}–{{ $publications->lastItem() }} of {{ $publications->total() }} {{ $publications->total() === 1 ? 'record' : 'records' }}@foreach ($activeSummary as $part) · {{ $part }}@endforeach
                        @endif
                    </p>

                    @if ($publications->total() === 0)
                        <x-empty-state class="mt-2" message="Nothing has been catalogued under these filters yet." />
                    @endif

                    @foreach ($groups as $group)
                        <section class="catalogue__decade" aria-labelledby="{{ $group['anchor'] }}">
                            <div class="catalogue__decade-head" data-rise>
                                <h2 id="{{ $group['anchor'] }}" class="catalogue__decade-title">{{ $group['label'] }}</h2>
                                <span class="catalogue__decade-count">{{ $group['count'] }} {{ $group['count'] === 1 ? 'record' : 'records' }}</span>
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
                                            <x-meta :items="[$publication->type?->label(), $publication->topic]" />
                                            <h3 id="publication-{{ $id }}-title" class="record__title">@typeset($publication->title)</h3>
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
                                            <ul class="record__links" aria-label="Links for {{ $publication->title }}">
                                                @if ($publication->doiUrl())
                                                    <li><a href="{{ $publication->doiUrl() }}" rel="noopener" class="record__doi"><span class="record__doi-label">DOI</span> <span class="font-mono">{{ $publication->doi }}</span></a></li>
                                                @endif
                                                @if (filled($publication->url))
                                                    <li><x-external-link :href="$publication->url" label="Read online" /></li>
                                                @endif
                                                @if ($file)
                                                    <li><x-file-link :media="$file" :href="$file->getUrl()" label="Download" /></li>
                                                @endif
                                            </ul>
                                            @endif

                                            <details class="cite" x-data="citePanel" data-default-style="{{ $citations[$id][0]['style']->value }}">
                                                <summary class="link-meta">Cite<span class="sr-only"> {{ $publication->title }}</span></summary>
                                                <div class="cite__panel">
                                                    <div class="cite__tabs" role="tablist" aria-label="Citation format" x-cloak>
                                                        @foreach ($citations[$id] as $citation)
                                                            <button type="button" role="tab" class="cite__tab" data-style="{{ $citation['style']->value }}"
                                                                id="cite-{{ $id }}-{{ $citation['style']->value }}-tab" aria-controls="cite-{{ $id }}-{{ $citation['style']->value }}"
                                                                x-bind:aria-selected="(style === '{{ $citation['style']->value }}').toString()"
                                                                x-bind:tabindex="style === '{{ $citation['style']->value }}' ? 0 : -1"
                                                                x-on:click="select('{{ $citation['style']->value }}')" x-on:keydown="move($event)">{{ $citation['label'] }}</button>
                                                        @endforeach
                                                    </div>
                                                    @foreach ($citations[$id] as $citation)
                                                        <div role="tabpanel" class="cite__format" id="cite-{{ $id }}-{{ $citation['style']->value }}" aria-labelledby="cite-{{ $id }}-{{ $citation['style']->value }}-tab"
                                                            data-format="{{ $citation['style']->value }}" x-show="style === '{{ $citation['style']->value }}'" tabindex="0">
                                                            <p class="cite__style">{{ $citation['label'] }} <span class="cite__edition">{{ $citation['edition'] }}</span></p>
                                                            @if ($citation['code'])
                                                                <pre class="cite__code" data-cite-text>{!! $citation['html'] !!}</pre>
                                                            @else
                                                                <p class="cite__text" data-cite-text>{!! $citation['html'] !!}</p>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                    <div class="cite__actions" x-cloak>
                                                        <button type="button" class="filter-pill" x-on:click="copy()"><span x-text="copied ? 'Copied' : 'Copy'">Copy</span></button>
                                                        <span class="sr-only" role="status" x-text="copied ? 'Citation copied to the clipboard' : ''"></span>
                                                    </div>
                                                </div>
                                            </details>
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
