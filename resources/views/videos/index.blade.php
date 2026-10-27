@inject('pageCopy', 'App\Services\PageContentService')
<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-chapter-opening>

        @if ($featured === null)
            <x-empty-state class="mt-6" :message="$pageCopy->text('videos', 'empty')" />
        @else
            <article class="lecture-hall mt-16" aria-labelledby="featured-title" data-rise>
                <x-video-player :player="$player" :title="$featured->title" :featured="true" />
                <div class="lecture-hall__label">
                    <div class="lecture-hall__text">
                        <p class="t-label text-accent-text">{{ $pageCopy->text('videos', 'featured_label') }}</p>
                        <h2 id="featured-title" class="lecture-hall__title" @contentLang($featured)>
                            <a href="{{ route('videos.show', $featured->slug) }}" class="text-ink">{{ $featured->title }}</a>
                        </h2>
                        <x-video-label :video="$featured" />
                    </div>
                    @if (filled($featured->description))
                        <p class="t-body max-w-measure text-ink-muted" @contentLang($featured, 'description')>{{ \Illuminate\Support\Str::limit(trim($featured->description), \App\Services\VideosPageService::DESCRIPTION_LIMIT) }}</p>
                    @endif
                </div>
            </article>

            @if ($videos->isNotEmpty())
                <h2 class="t-label mt-24 text-accent-text" id="all-recordings" data-rise>{{ $pageCopy->text('videos', 'all_label') }}</h2>
                <hr class="rule-brass mt-4" data-rule>
                <ul class="mt-12 grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3" data-rise-group aria-labelledby="all-recordings">
                    @foreach ($videos as $video)
                        @php $url = route('videos.show', $video->slug); @endphp
                        <li>
                            <article class="media-card flex h-full flex-col gap-5">
                                <a href="{{ $url }}" class="media-card__media" tabindex="-1" aria-hidden="true">
                                    @if ($video->thumbnail())
                                        <x-photo :media="$video->thumbnail()" :aspect="\App\Constants\VideoEmbed::ASPECT" :captioned="false" sizes="(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw" />
                                    @else
                                        <div class="media-card__placeholder glow-brass rounded-card" style="aspect-ratio: {{ \App\Constants\VideoEmbed::ASPECT }}"></div>
                                    @endif
                                </a>
                                <div class="flex flex-col gap-2">
                                    <x-video-label :video="$video" />
                                    <h3 class="font-display text-2xl font-light leading-snug text-ink" @contentLang($video)>
                                        <a href="{{ $url }}" class="text-ink">{{ $video->title }}</a>
                                    </h3>
                                    @if (filled($video->description))
                                        <p class="t-body text-ink-muted" @contentLang($video, 'description')>{{ \Illuminate\Support\Str::limit(trim($video->description), \App\Services\VideosPageService::DESCRIPTION_LIMIT) }}</p>
                                    @endif
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </section>
</x-layouts.public>
