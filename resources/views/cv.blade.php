{{--
    View-only CV, set as a ledger: years on the left in old-style figures, entries on the right, each
    section opened by <x-chapter-opening>, with a slim sticky index of sections on wide screens.
    No download, print or file link anywhere on this page, and the print stylesheet hides it entirely.
--}}
@inject('pageCopy', 'App\Services\PageContentService')
<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <x-reading-progress target="#cv-body" />

    <section id="cv-body" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <div class="cv-ledger">
            <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$section['title']" level="1" id="section-title">
                {{ $section['summary'] }}
            </x-chapter-opening>

            @if ($groups === [])
                <x-empty-state class="mt-6" :message="$pageCopy->text('cv', 'empty')" />
            @else
                @if ($lastUpdated)
                    <p class="cv-ledger__updated" data-rise><span class="smallcaps">{{ $pageCopy->text('cv', 'updated_label') }}</span> <x-date :date="$lastUpdated" /></p>
                @endif

                <div class="cv-ledger__layout">
                    <nav class="cv-ledger__index" aria-labelledby="cv-index-title">
                        <p id="cv-index-title" class="t-label text-accent-text">{{ $pageCopy->text('cv', 'index_label') }}</p>
                        <ol class="cv-ledger__index-list">
                            @foreach ($summary as $item)
                                <li>
                                    <a href="#{{ $item['anchor'] }}">
                                        <span>{{ $item['label'] }}</span>
                                        <span class="cv-ledger__index-count">@num($item['count'])</span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>

                    <div class="cv-ledger__sections">
                        @foreach ($groups as $group)
                            <section id="{{ $group['anchor'] }}" aria-labelledby="{{ $group['anchor'] }}-title" class="cv-ledger__section">
                                <x-chapter-opening :label="trans_choice('{1} :count entry|[2,*] :count entries', $group['count'], ['count' => app(\App\Services\LocaleService::class)->digits($group['count'])])" :title="$group['label']" level="2" :id="$group['anchor'].'-title'" />

                                <ol class="ledger" data-rise-group>
                                    @foreach ($group['entries'] as $entry)
                                        <li class="ledger__row">
                                            <p class="ledger__years">{{ $entry->yearRange() ?? '' }}</p>
                                            <div class="ledger__entry">
                                                <h3 @contentLang($entry) class="ledger__title">@typeset($entry->title)</h3>
                                                @if (filled($entry->organisation))
                                                    <p @contentLang($entry, 'organisation') class="ledger__organisation">@typeset($entry->organisation)</p>
                                                @endif
                                                @if (filled($entry->role_description))
                                                    <p @contentLang($entry, 'role_description') class="ledger__description">@typeset($entry->role_description)</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </section>
                        @endforeach
                    </div>
                </div>

                <x-seal />
            @endif

            <x-folio :section="$section['label']" position="bottom" class="mt-16" />
        </div>

        {{-- Shown on paper only: the CV itself is never printed. --}}
        <p class="cv-print-notice">{{ $pageCopy->text('cv', 'print_notice', [':name' => __(\App\Constants\BrandMark::TITLE), ':url' => $section['url']]) }}</p>
    </section>
</x-layouts.public>
