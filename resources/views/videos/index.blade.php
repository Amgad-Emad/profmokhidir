<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-section-header>

        @if ($videos->isEmpty())
            <x-empty-state class="mt-6" message="Recordings of lectures, interviews and events will appear here once they are published." />
        @else
            <ul class="mt-16 grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3" data-rise-group>
                @foreach ($videos as $video)
                    @php
                        $url = route('videos.show', $video->slug);
                        $meta = array_values(array_filter([$video->event, $video->location], 'filled'));
                    @endphp
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
                                @if ($video->date || $meta !== [])
                                    <p class="t-label text-bronze">
                                        @if ($video->date)<x-date :date="$video->date" />@endif
                                        @foreach ($meta as $index => $part)
                                            @if ($index > 0 || $video->date){{ \App\Constants\DateFormats::META_SEPARATOR }}@endif{{ $part }}
                                        @endforeach
                                    </p>
                                @endif
                                <h2 class="font-display text-2xl font-light leading-snug text-evergreen">
                                    <a href="{{ $url }}" class="text-evergreen">{{ $video->title }}</a>
                                </h2>
                                @if (filled($video->description))
                                    <p class="t-body text-ash">{{ \Illuminate\Support\Str::limit(trim($video->description), \App\Services\VideosPageService::DESCRIPTION_LIMIT) }}</p>
                                @endif
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.public>
