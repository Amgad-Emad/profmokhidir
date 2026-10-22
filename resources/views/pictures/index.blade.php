<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-chapter-opening>

        @if ($albums === [])
            <x-empty-state class="mt-6" message="Photographs will be gathered into albums here once they are published." />
        @else
            <nav class="exhibition-views mt-12 flex flex-wrap items-center gap-x-8 gap-y-4" aria-label="View" data-rise>
                <p class="t-label text-accent-text">{{ $total }} {{ \Illuminate\Support\Str::plural('photograph', $total) }} in {{ count($albums) }} {{ \Illuminate\Support\Str::plural('album', count($albums)) }}</p>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($views as $option)
                        <li><a href="{{ $option['url'] }}" class="filter-pill"{!! $option['active'] ? ' aria-current="true"' : '' !!}>{{ $option['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>

            @if ($view === \App\Constants\PictureView::Decades)
                <div class="decades" x-data="lightbox">
                    @foreach ($decades as $group)
                        @php $id = 'decade-'.($group['decade'] ?? 'undated'); @endphp
                        <section class="decade mt-20 first:mt-16" aria-labelledby="{{ $id }}">
                            <header class="decade__header">
                                <h2 id="{{ $id }}" class="t-heading font-display" data-rise>{{ $group['label'] }}</h2>
                                <p class="t-label text-accent-text" data-rise>{{ $group['count'] }} {{ \Illuminate\Support\Str::plural('photograph', $group['count']) }}</p>
                            </header>
                            <hr class="rule-brass mt-6" data-rule>
                            <ul class="wall mt-12" data-rise-group aria-label="Photographs from the {{ $group['label'] }}">
                                @foreach ($group['photos'] as $hung)
                                    <li class="wall__item" style="--span: {{ $hung['span'] }}">
                                        <x-photo :media="$hung['photo']" :lightbox="true" :label="true" :lift="true" :show-caption="true" :sizes="$hung['sizes']" />
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach

                    <x-lightbox label="Through the decades" />
                </div>
            @else
                <ul class="exhibition mt-16" data-rise-group>
                    @foreach ($albums as $entry)
                        <li class="exhibition__item">
                            <article class="exhibition__album">
                                <a href="{{ $entry['url'] }}" class="exhibition__cover" tabindex="-1" aria-hidden="true">
                                    @if ($entry['cover'])
                                        <x-photo :media="$entry['cover']" :captioned="false" :lift="true" sizes="(min-width: 1280px) 720px, (min-width: 640px) 58vw, 100vw" />
                                    @else
                                        <div class="media-card__placeholder glow-brass rounded-card" style="aspect-ratio: 4 / 3"></div>
                                    @endif
                                </a>
                                <div class="exhibition__label">
                                    <h2 class="exhibition__title">
                                        <a href="{{ $entry['url'] }}" class="text-ink">{{ $entry['album']->title }}</a>
                                    </h2>
                                    <p class="exhibition__meta smallcaps">
                                        @if ($entry['range'])<span>{{ $entry['range'] }}</span>{{ \App\Constants\DateFormats::META_SEPARATOR }}@endif<span>{{ $entry['count'] }} {{ \Illuminate\Support\Str::plural('photograph', $entry['count']) }}</span>
                                    </p>
                                    @if (filled($entry['album']->description))
                                        <p class="t-body max-w-measure text-ink-muted">{{ $entry['album']->description }}</p>
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
