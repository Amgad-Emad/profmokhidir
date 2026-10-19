<x-layouts.chat title="Community chat">
    <section class="mx-auto max-w-3xl px-4 py-20 sm:px-6" aria-labelledby="section-title">
        <x-section-header label="Scientific Community" title="Community chat" level="1" id="section-title">
            {{ \App\Constants\CommunityCopy::CHAT_INTRO }}
        </x-section-header>
        <x-empty-state class="mt-6" message="No channels have been opened yet. Please check back soon." />
        <x-button variant="outline" :href="$communityUrl">Back to the community page</x-button>
    </section>
</x-layouts.chat>
