{{-- Other Information as a catalogue index: items grouped by category, each set as a catalogue record. --}}
<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-folio :section="$section['label']" class="mb-16" />

        <x-chapter-opening :number="$chapter" :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-chapter-opening>

        @if ($categories === [])
            <x-empty-state class="mt-6" message="Further information will be added here as it becomes available." />
        @else
            <div class="catalogue-index">
                @if (count($categories) > 1)
                    <nav class="catalogue-index__toc" aria-label="Categories" data-rise>
                        <ul class="flex flex-wrap gap-x-8 gap-y-3">
                            @foreach ($categories as $category)
                                <li><a href="#{{ $category['anchor'] }}" class="link-meta">{{ $category['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                @foreach ($categories as $category)
                    <section class="catalogue__decade" aria-labelledby="{{ $category['anchor'] }}">
                        <div class="catalogue__decade-head" data-rise>
                            <h2 id="{{ $category['anchor'] }}" class="catalogue__decade-title">{{ $category['label'] }}</h2>
                            <span class="catalogue__decade-count">{{ $category['items']->count() }} {{ $category['items']->count() === 1 ? 'item' : 'items' }}</span>
                        </div>
                        <hr class="rule-brass" data-rule>

                        <ol class="catalogue__records" data-rise-group>
                            @foreach ($category['items'] as $item)
                                <li class="record">
                                    <p class="record__year" aria-hidden="true">{{ $item->date?->format('Y') }}</p>
                                    <article class="record__body" aria-labelledby="other-{{ $item->getKey() }}-title">
                                        <x-meta :items="[$item->typeLabel(), $item->date]" />
                                        <h3 id="other-{{ $item->getKey() }}-title" class="record__title">
                                            @if ($item->hasDetail())
                                                <a href="{{ route('other.show', $item->slug) }}">@typeset($item->title)</a>
                                            @else
                                                @typeset($item->title)
                                            @endif
                                        </h3>
                                        @if (filled($item->summary))
                                            <p class="record__summary">@typeset($item->summary)</p>
                                        @endif
                                        @if (filled($item->destination_url))
                                            <ul class="record__links"><li><x-external-link :href="$item->destination_url" label="Visit" /></li></ul>
                                        @elseif ($item->hasDetail())
                                            <ul class="record__links"><li><a href="{{ route('other.show', $item->slug) }}" class="link-meta">Read more<span class="sr-only"> about {{ $item->title }}</span></a></li></ul>
                                        @endif
                                    </article>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach
            </div>
        @endif

        <x-folio :section="$section['label']" position="bottom" class="mt-16" />
    </section>
</x-layouts.public>
