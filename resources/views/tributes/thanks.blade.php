<x-layouts.public :title="__('Thank you')" :description="$message" :noindex="true" breadcrumb-tone="ivory">
    <section class="glow-brass on-dark bg-surface text-ink" aria-labelledby="section-title">
        <div class="mx-auto flex min-h-[calc(100svh-5rem)] max-w-3xl flex-col justify-center px-4 py-24 sm:px-6 lg:py-32">
            <p class="t-label text-accent-text" data-rise>{{ $section['label'] }}</p>
            <hr class="rule-brass mt-4" data-rule>
            <x-seal class="seal--open" tone="ivory" :size="56" />
            <h1 id="section-title" class="t-quote mt-10 text-ink-strong" data-rise role="status">{{ $message }}</h1>
            <div class="mt-12 flex flex-wrap gap-4" data-rise>
                <x-button :href="$indexUrl">{{ $copy['back'] }}</x-button>
                <x-button variant="primary" :href="route('home')">{{ $copy['home'] }}</x-button>
            </div>
        </div>
    </section>
</x-layouts.public>
