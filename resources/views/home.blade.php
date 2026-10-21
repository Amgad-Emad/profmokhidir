<x-layouts.public :description="$copy['line']">
    {{-- 1. Hero: evergreen, full viewport height, soft brass glow. Text and portrait recede with scroll. --}}
    <section class="hero glow-brass on-dark bg-evergreen text-champagne" aria-labelledby="hero-title">
        <div class="mx-auto grid min-h-[calc(100svh-5rem)] max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:gap-20 lg:py-24">
            <div class="flex flex-col items-start gap-8" data-scroll-scale>
                <p class="t-label text-brass">{{ $copy['label'] }}</p>
                <h1 id="hero-title" class="t-display">
                    <span class="block text-champagne">Professor</span>
                    <span class="block text-ivory">Mohamed Osman Khidir</span>
                </h1>
                <p class="t-body max-w-xl text-champagne">{{ $copy['line'] }}</p>
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
        </div>
    </section>

    {{-- 2. Quote band on ivory with a brass rule drawing above it. --}}
    <section class="bg-ivory" aria-label="Quotation">
        <div class="mx-auto max-w-3xl px-4 py-24 sm:px-6 lg:py-32">
            <hr class="rule-brass" data-rule>
            <blockquote class="t-quote pull-quote mt-10 text-evergreen" data-rise>
                <p><span class="hang-quote">&ldquo;</span>@typeset($copy['quote'])&rdquo;</p>
            </blockquote>
        </div>
    </section>

    {{-- 3. Chapters --}}
    <section class="bg-linen" aria-labelledby="chapter-life">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
            <x-chapter-opening :number="1" title="Life" id="chapter-life" />
            <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]" data-rise-group>
                @if ($profile && filled($profile->short_bio))
                    <p class="t-body max-w-measure text-evergreen">@typeset($profile->short_bio)</p>
                @else
                    <x-empty-state message="The biography is being prepared." />
                @endif
                <div class="flex flex-col items-start gap-4 lg:items-end">
                    @if ($profile && (filled($profile->title) || filled($profile->affiliation)))
                        <p class="t-label text-bronze lg:text-right">{{ implode(' · ', array_filter([$profile->title, $profile->affiliation])) }}</p>
                    @endif
                    <a href="{{ route('about') }}" class="btn-pill-outline text-evergreen">{{ $copy['readStory'] }}</a>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-ivory" aria-labelledby="chapter-work">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
            <x-chapter-opening :number="2" title="Work" id="chapter-work" />
            <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]" data-rise-group>
                <div>
                    @if ($publications->isNotEmpty())
                        <ol class="divide-y divide-stone border-y border-stone" aria-label="Latest publications">
                            @foreach ($publications as $publication)
                                <li class="flex flex-col gap-2 py-6">
                                    <p class="t-label text-bronze">{{ implode(' · ', array_filter([$publication->type?->label(), $publication->year])) }}</p>
                                    <h3 class="font-display text-2xl font-light leading-snug text-evergreen">
                                        <a href="{{ $publication->url ?? $publication->doiUrl() ?? route('publications') }}" class="text-evergreen">@typeset($publication->title)</a>
                                    </h3>
                                    @if (filled($publication->venue_publisher))
                                        <p class="text-base font-light text-ash">{{ $publication->venue_publisher }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <x-empty-state message="Publications will be listed here as they are catalogued." />
                    @endif
                    <a href="{{ route('publications') }}" class="btn-pill-outline mt-8 text-evergreen">All publications</a>
                </div>
                <div class="card card-ivory flex flex-col items-start gap-4 self-start">
                    @if ($awardCount > 0)
                        <p class="figures-lining font-display text-7xl font-light leading-none text-evergreen">{{ $awardCount }}</p>
                        <p class="t-label text-bronze">{{ $awardCount === 1 ? 'Award and honour' : 'Awards and honours' }}</p>
                    @else
                        <p class="t-label text-bronze">Awards and honours</p>
                    @endif
                    <p class="text-base font-light text-ash">The full record of education, appointments and honours is on the CV.</p>
                    <a href="{{ route('cv') }}" class="text-base font-medium text-evergreen underline decoration-brass underline-offset-4">CV and achievements</a>
                </div>
            </div>
        </div>
    </section>

    <section class="on-dark bg-evergreen text-champagne" aria-labelledby="chapter-tributes">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
            <x-chapter-opening :number="3" title="Tributes" tone="ivory" id="chapter-tributes" />
            @if ($tributes->isNotEmpty())
                <ul class="mt-10 grid gap-6 md:grid-cols-3" data-rise-group aria-label="Recent tributes">
                    @foreach ($tributes as $tribute)
                        <li class="card flex h-full flex-col gap-6 border-cypress bg-evergreen text-champagne">
                            <blockquote class="font-display text-xl font-light italic leading-relaxed">
                                <p>&ldquo;{{ $tribute->message }}&rdquo;</p>
                            </blockquote>
                            <footer class="mt-auto flex flex-col gap-1">
                                <p class="text-base font-medium text-ivory">{{ $tribute->name }}</p>
                                <p class="t-label text-brass">{{ implode(' · ', array_filter([$tribute->relationship?->label(), $tribute->location])) }}</p>
                            </footer>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="mt-10" data-rise-group>
                    <x-empty-state tone="ivory" message="No tributes have been published yet. Yours could be the first." />
                </div>
            @endif
            <div class="mt-10 flex flex-wrap gap-4" data-rise-group>
                <a href="{{ route('tributes') }}" class="btn-pill-filled">All tributes</a>
                <a href="{{ route('tributes.create') }}" class="btn-pill-outline">{{ $copy['leaveTribute'] }}</a>
            </div>
        </div>
    </section>

    {{-- 4. Quiet cards to the remaining sections. --}}
    <section class="bg-ivory" aria-label="More sections">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5" data-rise-group>
                @foreach ($sections as $item)
                    <li class="h-full">
                        <a href="{{ $item['url'] }}" class="section-card card flex h-full flex-col gap-3 text-evergreen no-underline hover:no-underline">
                            <span class="t-label text-bronze">{{ $item['label'] }}</span>
                            <span class="text-base font-light text-ash">{{ $item['summary'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <x-seal />
        </div>
    </section>
</x-layouts.public>
