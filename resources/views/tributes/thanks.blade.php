<x-layouts.public title="Thank you" :description="$message">
    <section class="glow-brass on-dark bg-evergreen text-champagne" aria-labelledby="section-title">
        <div class="mx-auto flex min-h-[calc(100svh-5rem)] max-w-3xl flex-col justify-center px-4 py-24 sm:px-6 lg:py-32">
            <p class="t-label text-brass" data-rise>{{ $section['label'] }}</p>
            <hr class="rule-brass mt-4" data-rule>
            <h1 id="section-title" class="t-quote mt-10 text-ivory" data-rise role="status">{{ $message }}</h1>
            <div class="mt-12 flex flex-wrap gap-4" data-rise>
                <a href="{{ $indexUrl }}" class="btn-pill-outline">Back to tributes</a>
                <a href="{{ route('home') }}" class="btn-pill-filled">Home</a>
            </div>
        </div>
    </section>
</x-layouts.public>
