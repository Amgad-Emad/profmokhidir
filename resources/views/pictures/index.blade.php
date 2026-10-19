<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-section-header>

        @if ($albums === [])
            <x-empty-state class="mt-6" message="Photographs will be gathered into albums here once they are published." />
        @else
            <ul class="mt-16 grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3" data-rise-group>
                @foreach ($albums as $entry)
                    <li>
                        <article class="media-card flex h-full flex-col gap-5">
                            <a href="{{ $entry['url'] }}" class="media-card__media" tabindex="-1" aria-hidden="true">
                                @if ($entry['cover'])
                                    <x-photo :media="$entry['cover']" aspect="4 / 3" :captioned="false" sizes="(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw" />
                                @else
                                    <div class="media-card__placeholder glow-brass rounded-card" style="aspect-ratio: 4 / 3"></div>
                                @endif
                            </a>
                            <div class="flex flex-col gap-2">
                                <p class="t-label text-bronze">{{ $entry['count'] }} {{ \Illuminate\Support\Str::plural('photograph', $entry['count']) }}</p>
                                <h2 class="font-display text-2xl font-light leading-snug text-evergreen">
                                    <a href="{{ $entry['url'] }}" class="text-evergreen">{{ $entry['album']->title }}</a>
                                </h2>
                                @if (filled($entry['album']->description))
                                    <p class="t-body text-ash">{{ $entry['album']->description }}</p>
                                @endif
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.public>
