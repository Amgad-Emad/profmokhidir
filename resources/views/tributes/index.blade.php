@inject('page', 'App\Services\TributesPageService')
<x-layouts.public :title="$section['title']" :description="$section['summary']" :image="$shareImage" breadcrumb-tone="ivory">
    {{-- Opens on evergreen: "Remembering" in brass, the heading in the quote style, and the seal. --}}
    <section class="glow-brass on-dark bg-surface text-ink" aria-labelledby="section-title">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
            <div class="section-header max-w-3xl">
                <p class="t-label text-accent-text" data-rise>{{ $copy['label'] }}</p>
                <hr class="rule-brass mt-4" data-rule>
                <h1 id="section-title" class="t-quote mt-8 text-ink-strong sm:text-[2.5rem] sm:leading-[1.25]" data-rise>@typeset($copy['heading'])</h1>
                <p class="t-body mt-6 max-w-2xl text-ink" data-rise>{{ $section['summary'] }}</p>
            </div>
            <x-seal class="seal--open" tone="ivory" :size="48" />
            <div class="mt-10 flex flex-wrap gap-4" data-rise>
                <a href="{{ $createUrl }}" class="btn-pill-filled">{{ $copy['leaveTribute'] }}</a>
            </div>
        </div>
    </section>

    @if ($featured)
        {{-- The frontispiece: the tribute editors chose, set large in the quote style. --}}
        <section class="bg-surface" aria-labelledby="featured-title" data-featured>
            <div class="mx-auto max-w-7xl px-4 pt-20 sm:px-6 lg:pt-28">
                <div class="frontispiece" data-rise-group>
                    <p id="featured-title" class="t-label text-accent-text">{{ $copy['featuredLabel'] }}</p>
                    <blockquote class="frontispiece__quote t-quote text-ink">
                        <p><span class="hang-quote">&ldquo;</span>@typeset($featured->message)&rdquo;</p>
                    </blockquote>
                    <p class="frontispiece__author smallcaps">@typeset($featured->name)</p>
                    <x-meta class="frontispiece__meta" :items="[$featured->relationship?->label(), $featured->location]" />
                    <a href="{{ $page->cardUrl($featured) }}" class="tribute-entry__card t-label" download="{{ \App\Constants\TributeCards::filename((int) $featured->getKey()) }}">Share card<span class="sr-only"> for the tribute from {{ $featured->name }} (PNG image)</span></a>
                </div>
                <x-ornament type="fleuron" />
            </div>
        </section>
    @endif

    {{-- The pages of the book. --}}
    <section class="bg-surface" aria-labelledby="book-title">
        <div class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:pb-28 {{ $featured ? 'pt-4' : 'pt-20 lg:pt-28' }}">
            <x-folio section="{{ $section['label'] }}" />

            <div class="mt-10 text-center" data-rise>
                <h2 id="book-title" class="t-label text-accent-text">{{ $copy['bookLabel'] }}</h2>
                <nav class="book-filters mt-6" aria-label="Filter by relationship">
                    <span class="book-filters__item"><a href="{{ $clearUrl }}" class="book-filter smallcaps"{!! $activeRelationship === null ? ' aria-current="true"' : '' !!}>All</a></span>
                    @foreach ($filters as $filter)
                        <span class="book-filters__item"><a href="{{ $filter['url'] }}" class="book-filter smallcaps"{!! $filter['active'] ? ' aria-current="true"' : '' !!}>{{ $filter['label'] }}</a></span>
                    @endforeach
                </nav>
            </div>

            @if ($tributes->total() === 0)
                <div class="mx-auto mt-6 max-w-3xl" data-rise-group>
                    @if ($activeRelationship === null && ! $featured)
                        <x-empty-state message="No tributes have been published yet. Yours could be the first." />
                        <a href="{{ $createUrl }}" class="btn-pill-outline text-ink">{{ $copy['leaveTribute'] }}</a>
                    @else
                        <x-empty-state :message="'No tributes from '.mb_strtolower($activeRelationship?->plural() ?? 'anyone else').' have been published yet.'" />
                    @endif
                </div>
            @else
                <ol class="book mt-14" data-rise-group data-book>
                    @foreach ($tributes as $tribute)
                        <li class="book__leaf">
                            <x-tribute-entry :tribute="$tribute" :card-url="$page->cardUrl($tribute)" />
                            <x-ornament />
                        </li>
                    @endforeach
                </ol>

                {{ $tributes->links('components.book-pagination') }}
            @endif

            <div class="mt-16 flex flex-wrap items-center justify-center gap-4" data-rise>
                <a href="{{ $createUrl }}" class="btn-pill-outline text-ink">{{ $copy['leaveTribute'] }}</a>
            </div>

            <x-folio section="{{ $section['label'] }}" position="bottom" class="mt-16" />
        </div>
    </section>
</x-layouts.public>
