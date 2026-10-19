<x-layouts.public :title="$video->title" :description="$description ?? $section['summary']">
    <article class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
        <div class="max-w-4xl">
            <x-section-header :label="$section['label']" :title="$video->title" level="1" id="section-title" />

            @php $meta = array_values(array_filter([$video->event, $video->location], 'filled')); @endphp
            @if ($video->date || $meta !== [])
                <p class="t-label mt-6 text-bronze" data-rise>
                    @if ($video->date)<x-date :date="$video->date" />@endif
                    @foreach ($meta as $index => $part)
                        @if ($index > 0 || $video->date){{ \App\Constants\DateFormats::META_SEPARATOR }}@endif{{ $part }}
                    @endforeach
                </p>
            @endif

            <div class="mt-10" data-rise>
                <x-video-player :player="$player" :title="$video->title" />
            </div>

            @if (filled($video->description))
                <p class="t-body mt-10 max-w-[68ch] text-evergreen" data-rise>{{ $video->description }}</p>
            @endif

            <nav class="mt-14 border-t border-stone pt-8" aria-label="More videos">
                <a href="{{ route('videos') }}" class="link-meta">All videos</a>
            </nav>
        </div>
    </article>
</x-layouts.public>
