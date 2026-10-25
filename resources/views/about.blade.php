@inject('pageCopy', 'App\Services\PageContentService')
<x-layouts.public :title="$section['title']" :description="$description" :schema="$schema" :type="\App\Constants\Seo::OG_TYPE_PROFILE">
    <x-reading-progress target="#about-body" />

    <section id="about-body" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            @if ($pageCopy->text('about', 'epigraph') !== '')
                <x-slot:epigraph>
                    <x-epigraph>{{ $pageCopy->text('about', 'epigraph') }}</x-epigraph>
                </x-slot:epigraph>
            @endif
        </x-chapter-opening>

        @if ($profile === null)
            <x-empty-state class="mt-6" :message="$pageCopy->text('about', 'empty')" />
        @else
            <div class="mt-16 grid gap-12 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:gap-16 xl:grid-cols-[minmax(0,1fr)_47rem]">
                <div class="lg:sticky lg:top-28 lg:self-start" data-rise-group>
                    @if ($portrait)
                        <x-photo :media="$portrait" sizes="(min-width: 1280px) 22rem, (min-width: 1024px) 36vw, 100vw" loading="eager" :show-caption="true" class="max-w-md" />
                    @endif
                    <div class="mt-8 flex flex-col gap-2">
                        <h2 class="font-display text-quote font-light leading-tight text-ink">@typeset($profile->name)</h2>
                        @if (filled($profile->title))
                            <p class="t-body text-ink">@typeset($profile->title)</p>
                        @endif
                        @if (filled($profile->affiliation))
                            <p class="text-base font-normal text-ink-muted">@typeset($profile->affiliation)</p>
                        @endif
                    </div>
                </div>

                <div class="sidenote-host flex flex-col gap-16" data-rise-group>
                    @if ($bio !== '')
                        <x-drop-cap class="prose-bio">{!! $bio !!}</x-drop-cap>
                    @endif

                    @if ($expertise !== [] || $facts !== [])
                        <x-ornament type="fleuron" class="!my-0" />
                    @endif

                    @if ($expertise !== [])
                        <section aria-labelledby="expertise-title">
                            <h2 id="expertise-title" class="t-label text-accent-text">{{ $pageCopy->text('about', 'expertise_heading') }}</h2>
                            <ul class="mt-5 flex flex-wrap gap-3">
                                @foreach ($expertise as $tag)
                                    <li class="tag t-label rounded-full border border-rule px-4 py-2 text-accent-text">{{ $tag }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($facts !== [])
                        <section aria-labelledby="facts-title">
                            <h2 id="facts-title" class="t-label text-accent-text">{{ $pageCopy->text('about', 'facts_heading') }}</h2>
                            <dl class="mt-5 grid gap-x-10 gap-y-6 border-t border-rule pt-6 sm:grid-cols-2">
                                @foreach ($facts as $fact)
                                    <div class="flex flex-col gap-1">
                                        <dt class="t-label text-accent-text">{{ $fact['label'] }}</dt>
                                        <dd class="figures-lining font-display text-[1.75rem] font-light leading-10 text-ink">{{ $fact['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endif

                    @if ($contact['email'] || $contact['note'] || $contact['phone'] || $contact['address'])
                        <section aria-labelledby="contact-title">
                            <h2 id="contact-title" class="t-label text-accent-text">{{ $pageCopy->text('about', 'contact_heading') }}</h2>
                            <div class="mt-5 flex flex-col gap-2 border-t border-rule pt-6">
                                @if ($contact['email'])
                                    <p class="t-body"><a href="mailto:{{ $contact['email'] }}" class="text-ink underline decoration-accent-line underline-offset-4">{{ $contact['email'] }}</a>@if ($contact['note'])<x-sidenote :number="1">@typeset($contact['note'])</x-sidenote>@endif</p>
                                @endif
                                @if ($contact['phone'])
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}" class="t-body text-ink underline decoration-accent-line underline-offset-4" data-contact-phone>{{ $contact['phone'] }}</a>
                                @endif
                                @if ($contact['address'])
                                    <p class="whitespace-pre-line text-base font-normal text-ink-muted" data-contact-address>{{ $contact['address'] }}</p>
                                @endif
                                @if ($contact['note'] && ! $contact['email'])
                                    <p class="text-base font-normal text-ink-muted">@typeset($contact['note'])</p>
                                @endif
                            </div>
                        </section>
                    @endif
                </div>
            </div>

            <nav class="mt-20" aria-label="Continue reading">
                <hr class="rule-brass" data-rule>
                <ul class="section-cards mt-10" data-rise-group>
                    @foreach ($links as $item)
                        <li><x-section-card :card="$item" level="2" /></li>
                    @endforeach
                </ul>
            </nav>

            <x-seal />
        @endif

        <x-folio :section="$section['label']" position="bottom" class="mt-16" />
    </section>
</x-layouts.public>
