<x-layouts.public :title="$title" description="Share a memory of Professor Khidir. Tributes are read before they are published.">
    <section class="mx-auto max-w-3xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$title" level="1" id="section-title">
            Share a memory, a lesson or a few words of thanks. Every tribute is read before it is published.
        </x-section-header>

        <form method="POST" action="{{ $storeUrl }}" class="mt-12 space-y-8" data-rise>
            @csrf

            @error(\App\Http\Controllers\TributeController::LIMIT_ERROR)
                <p class="rounded-card border border-stone bg-linen px-4 py-3 text-base font-light text-bronze" role="alert">{{ $message }}</p>
            @enderror

            <x-input name="name" label="Your name" required autocomplete="name" />

            <x-select name="relationship" label="How you knew him" :options="$relationships" placeholder="Choose one" required />

            <x-input name="location" label="Location (optional)" autocomplete="address-level2" hint="Town or city and country, for example Khartoum, Sudan." />

            <x-textarea name="message" label="Your tribute" required :maxlength="$messageMaxLength" rows="8" />

            <x-input name="email" label="Email (optional)" type="email" autocomplete="email" hint="Never shown publicly." />

            {{-- Honeypot: hidden from people, filled by bots, never validated. --}}
            <div class="form-honeypot" aria-hidden="true">
                <label for="{{ \App\Http\Requests\StoreTributeRequest::HONEYPOT }}">Website</label>
                <input type="text" id="{{ \App\Http\Requests\StoreTributeRequest::HONEYPOT }}" name="{{ \App\Http\Requests\StoreTributeRequest::HONEYPOT }}" tabindex="-1" autocomplete="off" value="">
            </div>

            <div>
                <label class="flex items-start gap-3 text-base font-light text-ash">
                    <input type="checkbox" name="consent" value="1" required class="mt-1.5 size-4 shrink-0 rounded-sm border-stone accent-evergreen" @checked(old('consent'))>
                    <span>I agree that this tribute may be published on this site with my name, relationship and location.</span>
                </label>
                @error('consent')
                    <p class="mt-2 text-sm text-bronze" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <x-button>Send tribute</x-button>
                <x-button variant="outline" :href="$indexUrl">Back to tributes</x-button>
            </div>
        </form>
    </section>
</x-layouts.public>
