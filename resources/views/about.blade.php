<x-layouts.public :title="$section['title']" :description="$description" :schema="$schema" :type="\App\Constants\Seo::OG_TYPE_PROFILE">
    <x-reading-progress target="#about-body" />

    {{-- Only what an editor entered shows: the heading, epigraph and small headings are left out when empty. --}}
    <section id="about-body" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" {!! $copy['title'] !== '' ? 'aria-labelledby="section-title"' : 'aria-label="'.e($section['title']).'"' !!}>
        {{-- In the editor's preview, each region names the editor section that changes it (@editSection). --}}
        <div @editSection('opening')>
        @if ($copy['title'] !== '')
            <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$copy['title']" level="1" id="section-title" field="page:title">
                @if ($copy['epigraph'] !== '')
                    <x-slot:epigraph>
                        <x-epigraph><span @editField('page:epigraph')>{{ $copy['epigraph'] }}</span></x-epigraph>
                    </x-slot:epigraph>
                @endif
            </x-chapter-opening>
        @else
            {{-- The page keeps one level-one heading for assistive technology, out of sight. --}}
            <h1 id="section-title" class="sr-only">{{ $section['title'] }}</h1>
            @if ($copy['epigraph'] !== '')
                <x-epigraph><span @editField('page:epigraph')>{{ $copy['epigraph'] }}</span></x-epigraph>
            @endif
        @endif
        </div>
        @if ($profile !== null)
            <div class="mt-16 grid gap-12 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:gap-16 xl:grid-cols-[minmax(0,1fr)_47rem]">
                <div class="lg:sticky lg:top-28 lg:self-start" data-rise-group>
                    @if ($portrait)
                        <div @editSection('portrait')><x-photo :media="$portrait" sizes="(min-width: 1280px) 22rem, (min-width: 1024px) 36vw, 100vw" loading="eager" :show-caption="true" class="max-w-md" /></div>
                    @endif
                    @if ($person['name'] !== '' || $person['title'] !== '' || $person['affiliation'] !== '')
                        <div @class(['flex flex-col gap-2', 'mt-8' => $portrait])@editSection('identity')>
                            @if ($person['name'] !== '')
                                <h2 class="font-display text-quote font-light leading-tight text-ink"@editField('profile:name')>@typeset($person['name'])</h2>
                            @endif
                            @if ($person['title'] !== '')
                                <p class="t-body text-ink"@editField('profile:title')>@typeset($person['title'])</p>
                            @endif
                            @if ($person['affiliation'] !== '')
                                <p class="text-base font-normal text-ink-muted"@editField('profile:affiliation')>@typeset($person['affiliation'])</p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="sidenote-host flex flex-col gap-16" data-rise-group>
                    @if ($bio !== '')
                        <div @editSection('biography')><x-drop-cap class="prose-bio">{!! $bio !!}</x-drop-cap></div>
                    @endif

                    @if ($expertise !== [] || $facts !== [])
                        <x-ornament type="fleuron" class="!my-0" />
                    @endif

                    @if ($expertise !== [])
                        <section{!! $copy['expertise'] !== '' ? ' aria-labelledby="expertise-title"' : '' !!} @editSection('expertise')>
                            @if ($copy['expertise'] !== '')
                                <h2 id="expertise-title" class="t-label text-accent-text"@editSection('around-the-listing')@editField('page:expertise_heading')>{{ $copy['expertise'] }}</h2>
                            @endif
                            <ul @class(['flex flex-wrap gap-3', 'mt-5' => $copy['expertise'] !== ''])>
                                @foreach ($expertise as $tag)
                                    <li class="tag t-label rounded-full border border-rule px-4 py-2 text-accent-text">{{ $tag }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($facts !== [])
                        <section{!! $copy['facts'] !== '' ? ' aria-labelledby="facts-title"' : '' !!} @editSection('expertise')>
                            @if ($copy['facts'] !== '')
                                <h2 id="facts-title" class="t-label text-accent-text"@editSection('around-the-listing')@editField('page:facts_heading')>{{ $copy['facts'] }}</h2>
                            @endif
                            <dl @class(['grid gap-x-10 gap-y-6 border-t border-rule pt-6 sm:grid-cols-2', 'mt-5' => $copy['facts'] !== ''])>
                                @foreach ($facts as $fact)
                                    <div class="flex flex-col gap-1">
                                        <dt class="t-label text-accent-text"@editField('profile:key_facts.'.$fact['index'].'.label')>{{ $fact['label'] }}</dt>
                                        <dd class="figures-lining font-display text-[1.75rem] font-light leading-10 text-ink"@editField('profile:key_facts.'.$fact['index'].'.value')>{{ $fact['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endif

                    @if ($contact['email'] || $contact['note'] || $contact['phone'] || $contact['address'])
                        <section{!! $copy['contact'] !== '' ? ' aria-labelledby="contact-title"' : '' !!} @editSection('contact')>
                            @if ($copy['contact'] !== '')
                                <h2 id="contact-title" class="t-label text-accent-text"@editSection('around-the-listing')@editField('page:contact_heading')>{{ $copy['contact'] }}</h2>
                            @endif
                            <div @class(['flex flex-col gap-2 border-t border-rule pt-6', 'mt-5' => $copy['contact'] !== ''])>
                                @if ($contact['email'])
                                    <p class="t-body"><a href="mailto:{{ $contact['email'] }}" class="text-ink underline decoration-accent-line underline-offset-4"@editField('profile:contact_email_public')>{{ $contact['email'] }}</a>@if ($contact['note'])<x-sidenote :number="1"><span @editField('profile:contact_note')>@typeset($contact['note'])</span></x-sidenote>@endif</p>
                                @endif
                                @if ($contact['phone'])
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}" class="t-body text-ink underline decoration-accent-line underline-offset-4" data-contact-phone @editField('profile:contact_phone')>{{ $contact['phone'] }}</a>
                                @endif
                                @if ($contact['address'])
                                    <p class="whitespace-pre-line text-base font-normal text-ink-muted" data-contact-address @editField('profile:contact_address')>{{ $contact['address'] }}</p>
                                @endif
                                @if ($contact['note'] && ! $contact['email'])
                                    <p class="text-base font-normal text-ink-muted"@editField('profile:contact_note')>@typeset($contact['note'])</p>
                                @endif
                            </div>
                        </section>
                    @endif
                </div>
            </div>

            @if ($links !== [])
                <nav class="mt-20" aria-label="{{ __('Continue reading') }}">
                    <hr class="rule-brass" data-rule>
                    <ul class="section-cards mt-10" data-rise-group>
                        @foreach ($links as $item)
                            <li><x-section-card :card="$item" level="2" /></li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            <x-seal />
        @endif

        <x-folio :section="$section['label']" position="bottom" class="mt-16" />
    </section>
</x-layouts.public>
