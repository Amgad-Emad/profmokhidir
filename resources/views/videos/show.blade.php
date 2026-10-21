<x-layouts.public :title="$video->title" :description="$description ?? $section['summary']">
    <article class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
        <div class="max-w-4xl">
            <x-section-header :label="$section['label']" :title="$video->title" level="1" id="section-title" />

            <x-video-label :video="$video" class="mt-6" data-rise />

            <div class="mt-10" data-rise>
                <x-video-player :player="$player" :title="$video->title" />
            </div>

            @if (filled($video->description))
                <p class="t-body mt-10 max-w-measure text-evergreen" data-rise>{{ $video->description }}</p>
            @endif

            @if ($player['captions_url'] && $player['kind'] !== \App\Constants\VideoEmbed::KIND_FILE)
                <p class="mt-6" data-rise><x-external-link :href="$player['captions_url']" label="Captions for this recording (WebVTT)" /></p>
            @endif
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-24" aria-labelledby="same-event">
                <h2 id="same-event" class="t-label text-bronze" data-rise>More from this event</h2>
                <hr class="rule-brass mt-4" data-rule>
                <ul class="mt-12 grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3" data-rise-group>
                    @foreach ($related as $other)
                        @php $url = route('videos.show', $other->slug); @endphp
                        <li>
                            <article class="media-card flex h-full flex-col gap-5">
                                <a href="{{ $url }}" class="media-card__media" tabindex="-1" aria-hidden="true">
                                    @if ($other->thumbnail())
                                        <x-photo :media="$other->thumbnail()" :aspect="\App\Constants\VideoEmbed::ASPECT" :captioned="false" sizes="(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw" />
                                    @else
                                        <div class="media-card__placeholder glow-brass rounded-card" style="aspect-ratio: {{ \App\Constants\VideoEmbed::ASPECT }}"></div>
                                    @endif
                                </a>
                                <div class="flex flex-col gap-2">
                                    <x-video-label :video="$other" />
                                    <h3 class="font-display text-2xl font-light leading-snug text-evergreen">
                                        <a href="{{ $url }}" class="text-evergreen">{{ $other->title }}</a>
                                    </h3>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <nav class="mt-20 border-t border-stone pt-8" aria-label="More videos">
            <a href="{{ route('videos') }}" class="link-meta">All videos</a>
        </nav>
    </article>
</x-layouts.public>
