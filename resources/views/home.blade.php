@php
    // Only what an editor entered in Admin › Pages › Home shows: a section with nothing in it is left out.
    $showHero = ! $hidden[\App\Constants\PageDefinitions\HomeBlocks::SECTION_HERO] && ($copy['label'] !== '' || $copy['headingFirst'] !== '' || $copy['headingSecond'] !== '' || $copy['line'] !== '' || $copy['epigraph'] !== '' || $copy['readStory'] || $copy['leaveTribute'] || $portrait);
    $showChapters = ! $hidden[\App\Constants\PageDefinitions\HomeBlocks::SECTION_CHAPTERS] && $chapters !== [];
    $showNumbers = ! $hidden[\App\Constants\PageDefinitions\HomeBlocks::SECTION_NUMBERS] && $stats !== [];
    $showContinue = ! $hidden[\App\Constants\PageDefinitions\HomeBlocks::SECTION_CONTINUE] && ($stories !== [] || $copy['closing'] !== '');
@endphp
<x-layouts.public :description="$description" :hero="$showHero">
    {{-- 1. Hero: evergreen, full viewport height, soft brass glow, under the transparent masthead. Text and portrait recede slowly with scroll. --}}
    @if ($showHero)
    <section class="hero glow-brass on-dark bg-surface text-ink" {!! $copy['headingFirst'] !== '' || $copy['headingSecond'] !== '' ? 'aria-labelledby="hero-title"' : 'aria-label="'.e(__('Introduction')).'"' !!} {{ \App\Constants\Masthead::HERO_ATTRIBUTE }}>
        <div class="hero__inner relative mx-auto grid max-w-7xl items-center gap-12 px-4 pb-36 sm:px-6 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:gap-20">
            <div class="flex flex-col items-start gap-8" data-scroll-scale>
                @if ($copy['label'] !== '')
                    <p class="t-label text-accent-text">{{ $copy['label'] }}</p>
                @endif
                @if ($copy['headingFirst'] !== '' || $copy['headingSecond'] !== '')
                    <h1 id="hero-title" class="t-display">
                        @if ($copy['headingFirst'] !== '')
                            <span class="block text-ink">{{ $copy['headingFirst'] }}</span>
                        @endif
                        @if ($copy['headingSecond'] !== '')
                            <span class="block text-ink-strong">{{ $copy['headingSecond'] }}</span>
                        @endif
                    </h1>
                @endif
                @if ($copy['line'] !== '')
                    <p class="t-body max-w-xl text-ink">{{ $copy['line'] }}</p>
                @endif
                @if ($copy['epigraph'] !== '')
                    <x-epigraph tone="ivory" class="hero__epigraph">@typeset($copy['epigraph'])</x-epigraph>
                @endif
                @if ($copy['readStory'] || $copy['leaveTribute'])
                    <div class="flex flex-wrap gap-4">
                        @if ($copy['readStory'])
                            <x-button variant="primary" :href="$copy['readStory']['url']">{{ $copy['readStory']['label'] }}</x-button>
                        @endif
                        @if ($copy['leaveTribute'])
                            <x-button :href="$copy['leaveTribute']['url']">{{ $copy['leaveTribute']['label'] }}</x-button>
                        @endif
                    </div>
                @endif
            </div>

            @if ($portrait)
                <div class="w-full max-w-sm justify-self-center lg:max-w-none lg:justify-self-end" data-scroll-scale>
                    <x-photo :media="$portrait" tone="ivory" sizes="(min-width: 1024px) 40vw, (min-width: 640px) 24rem, 100vw" loading="eager" aspect="4 / 5" class="hero__portrait" />
                </div>
            @endif

            @if ($copy['scroll'] !== '' && $showChapters)
                <a href="#{{ $anchor }}" class="scroll-cue t-label text-accent-text">
                    <span>{{ $copy['scroll'] }}</span>@if ($copy['chapters'] !== '')<span class="sr-only"> {{ __('to :section', ['section' => $copy['chapters']]) }}</span>@endif
                    <span class="scroll-cue__line" aria-hidden="true" data-rule></span>
                </a>
            @endif
        </div>
    </section>
    @endif

    {{-- 2. A life in chapters: one chapter per decade, a brass hairline down the left, quote interludes between. --}}
    @if ($showChapters)
    <section class="life bg-surface text-ink" {!! $copy['chapters'] !== '' ? 'aria-labelledby="'.e($anchor).'"' : 'id="'.e($anchor).'"' !!}>
        <div class="mx-auto max-w-7xl px-4 pt-20 sm:px-6 lg:pt-28">
            @if ($copy['chapters'] !== '')
                <x-chapter-opening :title="$copy['chapters']" :id="$anchor">{{ $copy['chaptersIntro'] }}</x-chapter-opening>
            @elseif ($copy['chaptersIntro']->toHtml() !== '')
                <div class="t-body max-w-2xl text-ink-muted" data-rise>{{ $copy['chaptersIntro'] }}</div>
            @endif
        </div>

        @foreach ($chapters as $chapter)
            <article class="life-chapter" aria-labelledby="{{ $chapter['id'] }}">
                <div class="mx-auto max-w-7xl px-4 sm:px-6">
                    {{-- Without years the marker column shrinks to the numeral; with neither, the milestones take the row. --}}
                    <div @class(['life-chapter__inner', 'life-chapter__inner--no-years' => blank($chapter['years']) && filled($chapter['numeral']), 'life-chapter__inner--bare' => blank($chapter['years']) && blank($chapter['numeral'])]) data-scroll-line>
                        <span class="life-chapter__line" aria-hidden="true"></span>
                        <header class="life-chapter__marker">
                            @if (filled($chapter['numeral']))
                                <p class="t-numeral text-accent-text"><span class="sr-only">{{ __('Chapter') }} </span>{{ $chapter['numeral'] }}</p>
                            @endif
                            @if (filled($chapter['years']))
                                <h3 id="{{ $chapter['id'] }}" class="life-chapter__years">{{ $chapter['years'] }}</h3>
                            @else
                                <h3 id="{{ $chapter['id'] }}" class="sr-only">{{ __('Chapter') }} {{ $chapter['number'] }}</h3>
                            @endif
                        </header>
                        <div class="life-chapter__body">
                            <ol class="life-chapter__milestones" data-rise-group>
                                @foreach ($chapter['milestones'] as $milestone)
                                    <li class="milestone">
                                        <p class="milestone__year">{{ $milestone['years'] }}</p>
                                        <p class="milestone__title">@typeset($milestone['title'])</p>
                                        @if (filled($milestone['label']) || $milestone['detail'])
                                            <p class="milestone__meta">
                                                @if (filled($milestone['label']))
                                                    <span class="smallcaps text-accent-text">{{ $milestone['label'] }}</span>
                                                @endif
                                                @if (filled($milestone['label']) && $milestone['detail'])
                                                    <span aria-hidden="true" class="text-rule">·</span>
                                                @endif
                                                @if ($milestone['detail'])
                                                    <span>{{ $milestone['detail'] }}</span>
                                                @endif
                                            </p>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                            @if ($chapter['photo'])
                                <div class="life-chapter__photo" data-rise>
                                    <x-photo :media="$chapter['photo']" sizes="(min-width: 1024px) 20rem, (min-width: 768px) 40vw, 100vw" />
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </article>

            @if ($chapter['interlude'])
                <figure class="interlude glow-brass on-dark bg-surface text-ink">
                    <div class="mx-auto max-w-4xl px-8 py-24 lg:py-32">
                        <blockquote class="t-quote pull-quote" data-rise>
                            <p><span class="hang-quote">&ldquo;</span>@typeset($chapter['interlude']['quote'])&rdquo;</p>
                        </blockquote>
                        @if ($chapter['interlude']['credit'])
                            <figcaption class="interlude__credit" data-rise>
                                <span class="rule-brass-short" aria-hidden="true"></span>
                                <span class="smallcaps">@typeset($chapter['interlude']['credit'])</span>
                            </figcaption>
                        @endif
                    </div>
                </figure>
            @endif
        @endforeach

        <div class="pb-20 lg:pb-28" aria-hidden="true"></div>
    </section>
    @endif

    {{-- 4. His work in numbers: lining figures that rise in like everything else, never count up. --}}
    @if ($showNumbers)
    <section class="bg-surface-raised text-ink"{!! $copy['numbers'] !== '' ? ' aria-labelledby="work-in-numbers"' : '' !!}>
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
            @if ($copy['numbers'] !== '')
                <x-chapter-opening :title="$copy['numbers']" id="work-in-numbers" />
            @endif
            <dl class="figures" data-rise-group>
                @foreach ($stats as $stat)
                    <div class="figure">
                        <dt class="smallcaps figure__label">{{ $stat['label'] }}</dt>
                        <dd class="figures-lining figure__value">@num($stat['value'])</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
    @endif

    {{-- 5. Continue the story: three section cards across the content width. 6. The seal and the closing line. --}}
    @if ($showContinue)
    <section class="bg-surface text-ink"{!! $copy['continue'] !== '' ? ' aria-labelledby="continue-the-story"' : '' !!}>
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
            @if ($copy['continue'] !== '')
                <x-chapter-opening :title="$copy['continue']" id="continue-the-story" />
            @endif
            @if ($stories !== [])
                <ul class="section-cards mt-12" data-rise-group>
                    @foreach ($stories as $story)
                        <li><x-section-card :card="$story" /></li>
                    @endforeach
                </ul>
            @endif

            @if ($copy['closing'] !== '')
                <x-seal />
                <p class="t-quote mx-auto mt-8 max-w-2xl text-center text-ink" data-rise>{{ $copy['closing'] }}</p>
            @endif
        </div>
    </section>
    @endif
</x-layouts.public>
