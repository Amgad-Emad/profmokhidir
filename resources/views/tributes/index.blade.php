<x-layouts.public :title="$section['title']" :description="$section['summary']" :image="$shareImage">
    {{-- Opens on evergreen: label, brass rule, the heading in the quote style, and the call to leave a tribute. --}}
    <section class="glow-brass on-dark bg-evergreen text-champagne" aria-labelledby="section-title">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
            <div class="section-header max-w-3xl">
                <p class="t-label text-brass" data-rise>{{ $copy['label'] }}</p>
                <hr class="rule-brass mt-4" data-rule>
                <h1 id="section-title" class="t-quote mt-8 text-ivory sm:text-[2.5rem] sm:leading-[1.25]" data-rise>{{ $copy['heading'] }}</h1>
                <p class="t-body mt-6 max-w-2xl text-champagne" data-rise>{{ $section['summary'] }}</p>
            </div>
            <div class="mt-10 flex flex-wrap gap-4" data-rise>
                <a href="{{ $createUrl }}" class="btn-pill-filled">{{ $copy['leaveTribute'] }}</a>
            </div>
        </div>
    </section>

    <section class="bg-ivory" aria-label="Published tributes">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-28">
            @if ($tributes->total() === 0)
                <div data-rise-group>
                    <x-empty-state message="No tributes have been published yet. Yours could be the first." />
                    <a href="{{ $createUrl }}" class="btn-pill-outline text-evergreen">{{ $copy['leaveTribute'] }}</a>
                </div>
            @else
                <ul class="tribute-wall columns-1 gap-6 md:columns-2" data-rise-group>
                    @foreach ($tributes as $tribute)
                        <li class="mb-6 break-inside-avoid">
                            <article class="card card-ivory flex flex-col gap-6">
                                <blockquote class="font-display text-xl font-light italic leading-relaxed text-evergreen sm:text-2xl">
                                    <p class="whitespace-pre-line">&ldquo;{{ $tribute->message }}&rdquo;</p>
                                </blockquote>
                                <footer class="flex flex-col gap-1">
                                    <p class="text-base font-medium text-evergreen">{{ $tribute->name }}</p>
                                    <x-meta :items="[$tribute->relationship?->label(), $tribute->location]" />
                                </footer>
                            </article>
                        </li>
                    @endforeach
                </ul>

                {{ $tributes->links('components.pagination') }}
            @endif
        </div>
    </section>
</x-layouts.public>
