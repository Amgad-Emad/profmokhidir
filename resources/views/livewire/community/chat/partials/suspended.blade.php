@use('App\Constants\CommunityCopy')
@if ($live)
    <section class="mx-auto max-w-2xl px-4 py-16 sm:px-6" aria-labelledby="suspended-title-live" x-show="accessLost" x-cloak data-access-lost>
        <p class="t-label text-accent-text">{{ CommunityCopy::LABEL }}</p>
        <hr class="rule-brass mt-4 is-drawn">
        <h1 id="suspended-title-live" class="t-heading mt-8">{{ $suspendedHeading }}</h1>
        <p class="t-body mt-6 max-w-2xl text-ink-muted">{{ $suspendedMessage }}</p>
        <x-button variant="outline" :href="$communityUrl" class="mt-8">Back to the community page</x-button>
    </section>
@else
    <section class="mx-auto max-w-2xl px-4 py-16 sm:px-6" aria-labelledby="suspended-title" data-access-revoked>
        <x-section-header :label="CommunityCopy::LABEL" :title="$suspendedHeading" level="1" id="suspended-title">
            {{ $suspendedMessage }}
        </x-section-header>
        <x-button variant="outline" :href="$communityUrl" class="mt-8">Back to the community page</x-button>
    </section>
@endif
