@use('App\Constants\TributeForm')
@inject('typesetter', 'App\Services\TypesetterService')
<x-layouts.public :title="$title" description="Check your tribute before it is sent for review." :noindex="true">
    <section class="mx-auto max-w-3xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$title" level="1" id="section-title">
            {{ $intro }}
        </x-section-header>

        <p class="tribute-steps mt-8" data-rise data-step="{{ $step }}">
            <span class="smallcaps"><span class="sr-only">Step {{ TributeForm::position($step) }} of {{ count($steps) }}</span><span aria-hidden="true">Step {{ $typesetter->roman(TributeForm::position($step)) }} of {{ $typesetter->roman(count($steps)) }}</span></span>
            @foreach ($steps as $key => $label)
                @if ($key === $step)
                    <span class="smallcaps tribute-steps__current" aria-current="step">{{ $label }}</span>
                @else
                    <span class="smallcaps">{{ $label }}</span>
                @endif
            @endforeach
        </p>

        <div class="leaf mt-12" data-rise data-preview>
            <x-tribute-entry :tribute="$tribute" heading="2" />
        </div>

        <form novalidate method="POST" action="{{ $storeUrl }}" class="mt-12 space-y-8" data-rise>
            @csrf

            @error(\App\Http\Controllers\TributeController::LIMIT_ERROR)
                <p class="rounded-card border border-rule bg-surface-raised px-4 py-3 text-base font-light text-danger-tone" role="alert">{{ $message }}</p>
            @enderror

            <input type="hidden" name="name" value="{{ $data['name'] }}">
            <input type="hidden" name="relationship" value="{{ $data['relationship'] }}">
            <input type="hidden" name="location" value="{{ $data['location'] ?? '' }}">
            <input type="hidden" name="message" value="{{ $data['message'] }}">
            <input type="hidden" name="email" value="{{ $data['email'] ?? '' }}">

            {{-- Honeypot: hidden from people, filled by bots, never validated. --}}
            <div class="form-honeypot" aria-hidden="true">
                <label for="{{ \App\Http\Requests\StoreTributeRequest::HONEYPOT }}">Website</label>
                <input type="text" id="{{ \App\Http\Requests\StoreTributeRequest::HONEYPOT }}" name="{{ \App\Http\Requests\StoreTributeRequest::HONEYPOT }}" tabindex="-1" autocomplete="off" value="">
            </div>

            <x-form.checkbox name="consent" required>{{ $consent }}</x-form.checkbox>

            <div class="flex flex-wrap items-center gap-4">
                <x-button>Send tribute</x-button>
                <x-button type="submit" variant="quiet" formaction="{{ $editUrl }}" formnovalidate>Change something</x-button>
            </div>
        </form>
    </section>
</x-layouts.public>
