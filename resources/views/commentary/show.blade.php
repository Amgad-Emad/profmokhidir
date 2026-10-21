{{--
    A commentary as an essay: chapter opening with an optional epigraph, byline, date and reading time,
    a drop cap, sidenotes from the editor's footnote markers, a table of contents built from the headings
    (sticky beside the text on wide screens, a disclosure above it otherwise), a slim brass progress line
    under the header and "Further reading" by shared topic. Prints as an article (resources/css/archive.css).
--}}
<x-layouts.public :title="$commentary->title" :description="$commentary->summary" :type="\App\Constants\Seo::OG_TYPE_ARTICLE">
    <div class="reading-progress" x-data="readingProgress" data-target="#essay-body" aria-hidden="true"></div>

    <article class="essay mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-folio :section="$section['label']" class="mb-16" />

        <div @class(['essay__layout', 'essay__layout--contents' => $contents !== []])>
            @if ($contents !== [])
                <div class="essay__aside">
                    <nav class="essay__contents" aria-labelledby="essay-contents-title" x-data="essayContents">
                        <p id="essay-contents-title" class="t-label text-bronze">{{ \App\Constants\Essay::TOC_LABEL }}</p>
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

                <p class="essay__byline" data-rise>
                    @if (filled($commentary->byline))
                        <span class="smallcaps">@typeset($commentary->byline)</span>
                        @if ($commentary->date)<span aria-hidden="true">{{ \App\Constants\DateFormats::META_SEPARATOR }}</span>@endif
                    @endif
                    @if ($commentary->date)
                        <x-date :date="$commentary->date" class="whitespace-nowrap" />@if (filled($commentary->external_url))<x-sidenote :number="1" id="note-origin">First published at {{ parse_url($commentary->external_url, PHP_URL_HOST) ?: $commentary->external_url }}; the original is linked at the end.</x-sidenote>@endif
                    @endif
                    <span class="essay__reading-time smallcaps">{{ $readingTime }}</span>
                </p>

                @if (filled($commentary->summary))
                    <p class="essay__standfirst" data-rise>@typeset($commentary->summary)</p>
                @endif

                @if ($contents !== [])
                    <details class="essay__contents-inline" data-rise>
                        <summary class="link-meta">{{ \App\Constants\Essay::TOC_LABEL }}</summary>
                        <nav aria-label="{{ \App\Constants\Essay::TOC_LABEL }}">
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
                        <x-drop-cap class="prose-bio essay__body mt-10" data-rise>{!! $body !!}</x-drop-cap>
                    @endif

                    @if (filled($commentary->external_url))
                        <p class="mt-10" data-rise><x-external-link :href="$commentary->external_url" label="Read the original" /></p>
                    @endif
                </div>

                <x-ornament type="fleuron" />

                <p class="essay__print-source" aria-hidden="true">{{ route('commentary.show', $commentary->slug) }}</p>

                @if ($tags !== [])
                    <nav class="essay__topics" aria-label="Topics" data-rise>
                        <p class="t-label text-bronze">Topics</p>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($tags as $tag)
                                <li><a href="{{ $tag['url'] }}" class="filter-pill">{{ $tag['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                @if ($related->isNotEmpty())
                    <section class="further-reading mt-16" aria-labelledby="further-reading-title">
                        <h2 id="further-reading-title" class="t-label text-bronze" data-rise>{{ \App\Constants\Essay::FURTHER_READING_LABEL }}</h2>
                        <hr class="rule-brass mt-4" data-rule>
                        <ol class="further-reading__list" data-rise-group>
                            @foreach ($related as $item)
                                <li class="further-reading__item">
                                    @if ($item->date)
                                        <x-date :date="$item->date" class="t-label text-bronze" />
                                    @endif
                                    <a href="{{ route('commentary.show', $item->slug) }}" class="further-reading__title">@typeset($item->title)</a>
                                    @if (filled($item->summary))
                                        <p class="further-reading__summary">@typeset(\Illuminate\Support\Str::limit($item->summary, 160))</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                <nav class="essay__more mt-16" aria-label="More commentary" data-rise>
                    <hr class="rule-brass" data-rule>
                    <div class="mt-8 grid gap-8 sm:grid-cols-2">
                        <div>
                            @if ($previous)
                                <p class="t-label text-bronze">Previous</p>
                                <a href="{{ route('commentary.show', $previous->slug) }}" class="mt-3 block font-display text-xl font-light leading-snug text-evergreen">@typeset($previous->title)</a>
                            @endif
                        </div>
                        <div class="sm:text-right">
                            @if ($next)
                                <p class="t-label text-bronze">Next</p>
                                <a href="{{ route('commentary.show', $next->slug) }}" class="mt-3 block font-display text-xl font-light leading-snug text-evergreen">@typeset($next->title)</a>
                            @endif
                        </div>
                    </div>
                    <a href="{{ $section['url'] }}" class="btn-pill-outline mt-10 text-evergreen">All commentary</a>
                </nav>

                <x-seal />
            </div>
        </div>

        <x-folio :section="$section['label']" position="bottom" class="mt-16" />
    </article>
</x-layouts.public>
