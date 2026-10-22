<x-layouts.public :description="$copy['line']" :hero="true">
    {{-- 1. Hero: evergreen, full viewport height, soft brass glow, under the transparent masthead. Text and portrait recede slowly with scroll. --}}
    <section class="hero glow-brass on-dark bg-evergreen text-champagne" aria-labelledby="hero-title" {{ \App\Constants\Masthead::HERO_ATTRIBUTE }}>
        <div class="hero__inner relative mx-auto grid max-w-7xl items-center gap-12 px-4 pb-36 sm:px-6 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:gap-20">
            <div class="flex flex-col items-start gap-8" data-scroll-scale>
                <p class="t-label text-brass">{{ $copy['label'] }}</p>
                <h1 id="hero-title" class="t-display">
                    <span class="block text-champagne">Professor</span>
                    <span class="block text-ivory">Mohamed Osman Khidir</span>
                </h1>
                <p class="t-body max-w-xl text-champagne">{{ $copy['line'] }}</p>
                <x-epigraph tone="ivory" class="hero__epigraph">@typeset($copy['epigraph'])</x-epigraph>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('about') }}" class="btn-pill-filled">{{ $copy['readStory'] }}</a>
                    <a href="{{ route('tributes.create') }}" class="btn-pill-outline">{{ $copy['leaveTribute'] }}</a>
                </div>
            </div>

            @if ($portrait)
                <div class="w-full max-w-sm justify-self-center lg:max-w-none lg:justify-self-end" data-scroll-scale>
                    <x-photo :media="$portrait" tone="ivory" sizes="(min-width: 1024px) 40vw, (min-width: 640px) 24rem, 100vw" loading="eager" aspect="4 / 5" class="hero__portrait" />
                </div>
            @endif

            <a href="#{{ $anchor }}" class="scroll-cue t-label text-brass">
                <span>{{ $copy['scroll'] }}</span><span class="sr-only"> to {{ $copy['chapters'] }}</span>
                <span class="scroll-cue__line" aria-hidden="true" data-rule></span>
            </a>
        </div>
    </section>

    {{-- 2. A life in chapters: one chapter per decade, a brass hairline down the left, quote interludes between. --}}
    <section class="life bg-ivory text-evergreen" aria-labelledby="{{ $anchor }}">
        <div class="mx-auto max-w-7xl px-4 pt-20 sm:px-6 lg:pt-28">
            <x-chapter-opening :title="$copy['chapters']" :id="$anchor">
                Each decade of his life, drawn from the record of his education, appointments and honours.
            </x-chapter-opening>
        </div>

        @forelse ($chapters as $chapter)
            <article class="life-chapter" aria-labelledby="{{ $chapter['id'] }}">
                <div class="mx-auto max-w-7xl px-4 sm:px-6">
                    <div class="life-chapter__inner" data-scroll-line>
                        <span class="life-chapter__line" aria-hidden="true"></span>
                        <header class="life-chapter__marker">
                            <p class="t-numeral text-bronze"><span class="sr-only">Chapter </span>{{ $chapter['numeral'] }}</p>
                            <h3 id="{{ $chapter['id'] }}" class="life-chapter__years">{{ $chapter['years'] }}</h3>
                        </header>
                        <div class="life-chapter__body">
                            <ol class="life-chapter__milestones" data-rise-group>
                                @foreach ($chapter['milestones'] as $milestone)
                                    <li class="milestone">
                                        <p class="milestone__year">{{ $milestone['years'] }}</p>
                                        <p class="milestone__title">@typeset($milestone['title'])</p>
                                        <p class="milestone__meta">
                                            <span class="smallcaps text-bronze">{{ $milestone['label'] }}</span>
                                            @if ($milestone['detail'])
                                                <span aria-hidden="true" class="text-stone">·</span>
                                                <span>{{ $milestone['detail'] }}</span>
                                            @endif
                                        </p>
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
                <figure class="interlude glow-brass on-dark bg-evergreen text-champagne">
                    <div class="mx-auto max-w-4xl px-8 py-24 lg:py-32">
                        <blockquote class="t-quote pull-quote" data-rise>
                            <p><span class="hang-quote">&ldquo;</span>@typeset($chapter['interlude']->quote)&rdquo;</p>
                        </blockquote>
                        @if ($chapter['interlude']->credit())
                            <figcaption class="interlude__credit" data-rise>
                                <span class="rule-brass-short" aria-hidden="true"></span>
                                <span class="smallcaps">@typeset($chapter['interlude']->credit())</span>
                            </figcaption>
                        @endif
                    </div>
                </figure>
            @endif
        @empty
            <div class="mx-auto max-w-7xl px-4 pt-10 sm:px-6">
                <x-empty-state message="The chapters of his life will appear here as his record is catalogued." />
            </div>
        @endforelse

        <div class="pb-20 lg:pb-28" aria-hidden="true"></div>
    </section>

    {{-- 4. His work in numbers: lining figures that rise in like everything else, never count up. --}}
    <section class="bg-linen text-evergreen" aria-labelledby="work-in-numbers">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
            <x-chapter-opening :title="$copy['numbers']" id="work-in-numbers" />
            @if ($stats !== [])
                <dl class="figures" data-rise-group>
                    @foreach ($stats as $stat)
                        <div class="figure">
                            <dt class="smallcaps figure__label">{{ $stat['label'] }}</dt>
                            <dd class="figures-lining figure__value">{{ number_format($stat['value']) }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <div class="mt-10">
                    <x-empty-state message="The figures will appear once his publications, appointments and honours are catalogued." />
                </div>
            @endif
        </div>
    </section>

    {{-- 5. Continue the story: three quiet entry cards. 6. The seal and the closing line. --}}
    <section class="bg-ivory text-evergreen" aria-labelledby="continue-the-story">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
            <x-chapter-opening :title="$copy['continue']" id="continue-the-story" />
            <ul class="mt-12 grid gap-6 md:grid-cols-3" data-rise-group>
                @foreach ($stories as $story)
                    <li class="story-card card card-ivory">
                        @if ($story['photo'])
                            <x-photo :media="$story['photo']" sizes="(min-width: 768px) 30vw, 100vw" aspect="3 / 2" class="story-card__photo" />
                        @endif
                        <a href="{{ $story['url'] }}" class="story-card__link t-label text-bronze">{{ $story['title'] }}</a>
                        <p class="story-card__line">{{ $story['line'] }}</p>
                    </li>
                @endforeach
            </ul>

            <x-seal />
            <p class="t-quote mx-auto mt-8 max-w-2xl text-center text-evergreen" data-rise>{{ $copy['closing'] }}</p>
        </div>
    </section>
</x-layouts.public>
