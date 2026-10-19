<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-section-header>

        @if ($categories === [])
            <x-empty-state class="mt-6" message="Further information will be added here as it becomes available." />
        @else
            <div class="mt-14 flex flex-col gap-20">
                @foreach ($categories as $category)
                    <section aria-labelledby="{{ $category['anchor'] }}">
                        <h2 id="{{ $category['anchor'] }}" class="font-display text-quote font-light leading-tight" data-rise>{{ $category['label'] }}</h2>
                        <ul class="mt-8 grid gap-4 md:grid-cols-2" data-rise-group>
                            @foreach ($category['items'] as $item)
                                <li class="card flex h-full flex-col gap-3">
                                    <x-meta :items="[$item->typeLabel(), $item->date]" />
                                    <h3 class="font-display text-2xl font-light leading-snug text-evergreen">
                                        @if ($item->hasDetail())
                                            <a href="{{ route('other.show', $item->slug) }}" class="text-evergreen">{{ $item->title }}</a>
                                        @else
                                            {{ $item->title }}
                                        @endif
                                    </h3>
                                    @if (filled($item->summary))
                                        <p class="text-base font-light text-evergreen">{{ $item->summary }}</p>
                                    @endif
                                    @if (filled($item->destination_url))
                                        <p class="mt-auto pt-3"><x-external-link :href="$item->destination_url" label="Visit" /></p>
                                    @elseif ($item->hasDetail())
                                        <p class="mt-auto pt-3"><a href="{{ route('other.show', $item->slug) }}" class="link-meta">Read more<span class="sr-only"> about {{ $item->title }}</span></a></p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.public>
