@inject('pageCopy', 'App\Services\PageContentService')
<x-layouts.public :title="$album->title" :description="filled($album->description) ? $album->description : $section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$album->title" level="1" id="section-title">
            @if (filled($album->description)){{ $album->description }}@endif
        </x-chapter-opening>

        @if ($wall === [])
            <x-empty-state class="mt-6" :message="$pageCopy->text('pictures', 'album_empty')" />
        @else
            <p class="exhibition__meta smallcaps mt-8" data-rise>
                @if ($range)<span>{{ $range }}</span>{{ \App\Constants\DateFormats::META_SEPARATOR }}@endif<span>{{ count($wall) }} {{ \Illuminate\Support\Str::plural('photograph', count($wall)) }}</span>
            </p>

            <div class="gallery" x-data="lightbox">
                <ul class="wall mt-16" data-rise-group aria-label="Photographs in {{ $album->title }}">
                    @foreach ($wall as $hung)
                        <li @class(['wall__item', 'wall__item--featured' => $hung['featured']]) style="--span: {{ $hung['span'] }}">
                            <x-photo :media="$hung['photo']" :lightbox="true" :label="true" :lift="true" :show-caption="true" :sizes="$hung['sizes']" />
                        </li>
                    @endforeach
                </ul>

                <x-lightbox :label="$album->title" />
            </div>
        @endif

        <nav class="mt-24 border-t border-rule pt-8" aria-label="More albums">
            <ul class="flex flex-wrap gap-x-8 gap-y-3">
                <li><a href="{{ route('pictures') }}" class="link-meta">{{ $pageCopy->text('pictures', 'back_label') }}</a></li>
                <li><a href="{{ route('pictures', [\App\Constants\Archive::QUERY_VIEW => \App\Constants\PictureView::Decades->value]) }}" class="link-meta">Through the decades</a></li>
                @foreach ($albums as $other)
                    <li><a href="{{ route('pictures.show', $other->slug) }}" class="link-meta">{{ $other->title }}</a></li>
                @endforeach
            </ul>
        </nav>
    </section>
</x-layouts.public>
