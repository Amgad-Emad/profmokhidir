{{--
    A commentary as an essay: chapter opening with an optional epigraph, byline, date and reading time,
    a drop cap, sidenotes from the editor's footnote markers, a table of contents built from the headings
    (sticky beside the text on wide screens, a disclosure above it otherwise), a slim brass progress line
    under the header and "Further reading" by shared topic. Prints as an article (resources/css/archive.css).
--}}
@inject('pageCopy', 'App\Services\PageContentService')
<x-layouts.public :title="$commentary->title" :description="$commentary->summary" :type="\App\Constants\Seo::OG_TYPE_ARTICLE">
    <x-reading-progress target="#essay-body" />

    <article class="essay mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <div @class(['essay__layout', 'essay__layout--contents' => $contents !== []])>
            @if ($contents !== [])
                <div class="essay__aside">
                    <nav class="essay__contents" aria-labelledby="essay-contents-title" x-data="essayContents">
                        <p id="essay-contents-title" class="t-label text-accent-text">{{ __(\App\Constants\Essay::TOC_LABEL) }}</p>
                        <ol class="essay__contents-list">
                            @foreach ($contents as $entry)
                                <li @class(['essay__contents-sub' => $entry['level'] > 2])>
                                    <a href="#{{ $entry['id'] }}" x-bind:aria-current="isCurrent('{{ $entry['id'] }}') ? 'true' : null">@typeset($entry['text'])</a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                </div>
            @endif

            <div class="essay__main sidenote-host">
                <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$commentary->title" level="1" id="section-title">
                    @if (filled($commentary->epigraph))
                        <x-slot:epigraph>
                            <x-epigraph :attribution="$commentary->epigraph_attribution">@typeset($commentary->epigraph)</x-epigraph>
                        </x-slot:epigraph>
                    @endif
                </x-chapter-opening>

                <x-locale.fallback-note :model="$commentary" />

                <p class="essay__byline" data-rise>
                    @if (filled($commentary->byline))
                        <span class="smallcaps">@typeset($commentary->byline)</span>
                        @if ($commentary->date)<span aria-hidden="true">{{ \App\Constants\DateFormats::META_SEPARATOR }}</span>@endif
                    @endif
                    @if ($commentary->date)
                        <x-date :date="$commentary->date" class="whitespace-nowrap" />@if (filled($commentary->external_url))<x-sidenote :number="1" id="note-origin">{{ __('First published at :site; the original is linked at the end.', ['site' => parse_url($commentary->external_url, PHP_URL_HOST) ?: $commentary->external_url]) }}</x-sidenote>@endif
                    @endif
                    <span class="essay__reading-time smallcaps">{{ $readingTime }}</span>
                </p>

                @if (filled($commentary->summary))
                    <p class="essay__standfirst" data-rise @contentLang($commentary, 'summary')>@typeset($commentary->summary)</p>
                @endif

                @if ($contents !== [])
                    <details class="essay__contents-inline" data-rise>
                        <summary class="link-meta">{{ __(\App\Constants\Essay::TOC_LABEL) }}</summary>
                        <nav aria-label="{{ __(\App\Constants\Essay::TOC_LABEL) }}">
                            <ol class="essay__contents-list">
                                @foreach ($contents as $entry)
                                    <li @class(['essay__contents-sub' => $entry['level'] > 2])><a href="#{{ $entry['id'] }}">@typeset($entry['text'])</a></li>
                                @endforeach
                            </ol>
                        </nav>
                    </details>
                @endif

                <div id="essay-body">
                    @if ($body !== '')
                        <div @contentLang($commentary, 'body')><x-drop-cap class="prose-bio essay__body mt-10" data-rise>{!! $body !!}</x-drop-cap></div>
                    @endif

                    @if (filled($commentary->external_url))
                        <p class="mt-10" data-rise><x-external-link :href="$commentary->external_url" :label="__('Read the original')" /></p>
                    @endif
                </div>

                <x-ornament type="fleuron" />

                <p class="essay__print-source" aria-hidden="true">{{ route('commentary.show', $commentary->slug) }}</p>

                @if ($tags !== [])
                    <nav class="essay__topics" aria-label="{{ __('Topics') }}" data-rise>
                        <p class="t-label text-accent-text">{{ __('Topics') }}</p>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($tags as $tag)
                                <li><a href="{{ $tag['url'] }}" class="filter-pill">{{ $tag['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                @if ($related->isNotEmpty())
                    <section class="further-reading mt-16" aria-labelledby="further-reading-title">
                        <h2 id="further-reading-title" class="t-label text-accent-text" data-rise>{{ __(\App\Constants\Essay::FURTHER_READING_LABEL) }}</h2>
                        <hr class="rule-brass mt-4" data-rule>
                        <ol class="further-reading__list" data-rise-group>
                            @foreach ($related as $item)
                                <li class="further-reading__item">
                                    @if ($item->date)
                                        <x-date :date="$item->date" class="t-label text-accent-text" />
                                    @endif
                                    <a href="{{ route('commentary.show', $item->slug) }}" class="further-reading__title" @contentLang($item)>@typeset($item->title)</a>
                                    @if (filled($item->summary))
                                        <p class="further-reading__summary" @contentLang($item, 'summary')>@typeset(\Illuminate\Support\Str::limit($item->summary, 160))</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                <nav class="essay__more mt-16" aria-label="{{ __('More commentary') }}" data-rise>
                    <hr class="rule-brass" data-rule>
                    <div class="mt-8 grid gap-8 sm:grid-cols-2">
                        <div>
                            @if ($previous)
                                <p class="t-label text-accent-text">{{ __('Previous') }}</p>
                                <a href="{{ route('commentary.show', $previous->slug) }}" class="mt-3 block font-display text-xl font-light leading-snug text-ink" @contentLang($previous)>@typeset($previous->title)</a>
                            @endif
                        </div>
                        <div class="sm:text-end">
                            @if ($next)
                                <p class="t-label text-accent-text">{{ __('Next') }}</p>
                                <a href="{{ route('commentary.show', $next->slug) }}" class="mt-3 block font-display text-xl font-light leading-snug text-ink" @contentLang($next)>@typeset($next->title)</a>
                            @endif
                        </div>
                    </div>
                    <x-button :href="$section['url']" class="mt-10">{{ $pageCopy->text('commentary', 'back_label') }}</x-button>
                </nav>

                <x-seal />
            </div>
        </div>

        <x-folio :section="$section['label']" position="bottom" class="mt-16" />
    </article>
</x-layouts.public>
