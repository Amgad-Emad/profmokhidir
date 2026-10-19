{{-- View-only CV. No download, print or file link anywhere on this page. --}}
<x-layouts.public :title="$section['title']" :description="$section['summary']">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$section['title']" level="1" id="section-title">
            {{ $section['summary'] }}
        </x-section-header>

        @if ($groups === [])
            <x-empty-state class="mt-6" message="The curriculum vitae is being compiled and will appear here once it is published." />
        @else
            @if ($lastUpdated)
                <x-meta class="mt-10" :items="['Last updated', $lastUpdated]" data-rise />
            @endif

            <nav class="mt-8" aria-label="CV sections">
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5" data-rise-group>
                    @foreach ($summary as $item)
                        <li>
                            <a href="#{{ $item['anchor'] }}" class="section-card card card-ivory flex h-full flex-col gap-2 text-evergreen no-underline hover:no-underline">
                                <span class="font-display text-heading font-light leading-none">{{ $item['count'] }}</span>
                                <span class="t-label text-bronze">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="mt-20 flex flex-col gap-20">
                @foreach ($groups as $group)
                    <section id="{{ $group['anchor'] }}" aria-labelledby="{{ $group['anchor'] }}-title" class="scroll-mt-28">
                        <div class="flex items-baseline justify-between gap-6">
                            <h2 id="{{ $group['anchor'] }}-title" class="font-display text-quote font-light leading-tight" data-rise>{{ $group['label'] }}</h2>
                            <span class="t-label text-bronze" data-rise>{{ $group['count'] }}</span>
                        </div>
                        <ol class="mt-6 divide-y divide-stone border-y border-stone" data-rise-group>
                            @foreach ($group['entries'] as $entry)
                                <li class="grid gap-2 py-8 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-10">
                                    <p class="t-label pt-2 text-bronze">{{ $entry->yearRange() ?? '' }}</p>
                                    <div class="flex flex-col gap-1">
                                        <h3 class="font-display text-2xl font-light leading-snug text-evergreen">{{ $entry->title }}</h3>
                                        @if (filled($entry->organisation))
                                            <p class="font-display text-xl font-light italic text-ash">{{ $entry->organisation }}</p>
                                        @endif
                                        @if (filled($entry->role_description))
                                            <p class="t-body mt-3 max-w-[68ch] text-evergreen">{{ $entry->role_description }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.public>
