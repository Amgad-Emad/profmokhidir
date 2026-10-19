<x-layouts.public :title="$album->title" :description="filled($album->description) ? $album->description : $section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$album->title" level="1" id="section-title">
            @if (filled($album->description)){{ $album->description }}@endif
        </x-section-header>

        @if ($photos->isEmpty())
            <x-empty-state class="mt-6" message="This album has no photographs yet." />
        @else
            <div class="gallery" x-data="lightbox">
                <ul class="mt-16 grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3" data-rise-group aria-label="Photographs in {{ $album->title }}">
                    @foreach ($photos as $photo)
                        <li>
                            <x-photo :media="$photo" :lightbox="true" :show-caption="true" aspect="4 / 3" sizes="(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw" />
                        </li>
                    @endforeach
                </ul>

                <x-lightbox :label="$album->title" />
            </div>
        @endif

        <nav class="mt-20 border-t border-stone pt-8" aria-label="More albums">
            <ul class="flex flex-wrap gap-x-8 gap-y-3">
                <li><a href="{{ route('pictures') }}" class="link-meta">All albums</a></li>
                @foreach ($albums as $other)
                    <li><a href="{{ route('pictures.show', $other->slug) }}" class="link-meta">{{ $other->title }}</a></li>
                @endforeach
            </ul>
        </nav>
    </section>
</x-layouts.public>
