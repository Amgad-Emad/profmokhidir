<x-layouts.public :title="$section['title']" :description="$description" :schema="$schema" :type="\App\Constants\Seo::OG_TYPE_PROFILE">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$section['title']" level="1" id="section-title" />

        @if ($profile === null)
            <x-empty-state class="mt-6" message="The biography is being prepared and will appear here once it is published." />
        @else
            <div class="mt-14 grid gap-12 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:gap-20">
                <div class="lg:sticky lg:top-28 lg:self-start" data-rise-group>
                    @if ($portrait)
                        <x-photo :media="$portrait" sizes="(min-width: 1024px) 36vw, 100vw" loading="eager" :show-caption="true" class="max-w-md" />
                    @endif
                    <div class="mt-8 flex flex-col gap-2">
                        <h2 class="font-display text-quote font-light leading-tight text-evergreen">{{ $profile->name }}</h2>
                        @if (filled($profile->title))
                            <p class="t-body text-evergreen">{{ $profile->title }}</p>
                        @endif
                        @if (filled($profile->affiliation))
                            <p class="text-base font-light text-ash">{{ $profile->affiliation }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col gap-16" data-rise-group>
                    @if ($bio !== '')
                        <div class="prose-bio max-w-[68ch]">{!! $bio !!}</div>
                    @endif

                    @if ($expertise !== [])
                        <section aria-labelledby="expertise-title">
                            <h2 id="expertise-title" class="t-label text-bronze">Expertise</h2>
                            <ul class="mt-5 flex flex-wrap gap-3">
                                @foreach ($expertise as $tag)
                                    <li class="tag t-label rounded-full border border-stone px-4 py-2 text-bronze">{{ $tag }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($facts !== [])
                        <section aria-labelledby="facts-title">
                            <h2 id="facts-title" class="t-label text-bronze">Key facts</h2>
                            <dl class="mt-5 grid gap-x-10 gap-y-6 border-t border-stone pt-6 sm:grid-cols-2">
                                @foreach ($facts as $fact)
                                    <div class="flex flex-col gap-1">
                                        <dt class="t-label text-bronze">{{ $fact['label'] }}</dt>
                                        <dd class="t-body text-evergreen">{{ $fact['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endif

                    @if ($contact['email'] || $contact['note'])
                        <section aria-labelledby="contact-title">
                            <h2 id="contact-title" class="t-label text-bronze">Contact</h2>
                            <div class="mt-5 flex flex-col gap-2 border-t border-stone pt-6">
                                @if ($contact['email'])
                                    <a href="mailto:{{ $contact['email'] }}" class="t-body text-evergreen underline decoration-brass underline-offset-4">{{ $contact['email'] }}</a>
                                @endif
                                @if ($contact['note'])
                                    <p class="text-base font-light text-ash">{{ $contact['note'] }}</p>
                                @endif
                            </div>
                        </section>
                    @endif

                    <nav aria-label="Continue reading">
                        <hr class="rule-brass" data-rule>
                        <ul class="mt-8 grid gap-4 sm:grid-cols-3">
                            @foreach ($links as $item)
                                <li class="h-full">
                                    <a href="{{ $item['url'] }}" class="section-card card flex h-full flex-col gap-3 text-evergreen no-underline hover:no-underline">
                                        <span class="t-label text-bronze">{{ $item['label'] }}</span>
                                        <span class="text-base font-light text-ash">{{ $item['summary'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </div>
            </div>
        @endif
    </section>
</x-layouts.public>
