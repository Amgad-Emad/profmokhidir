@use('App\Constants\TributeForm')
@inject('typesetter', 'App\Services\TypesetterService')
<x-layouts.public :title="$title" :description="__('Share a memory of Professor Khidir. Tributes are read before they are published.')">
    <section class="mx-auto max-w-3xl px-4 py-20 sm:px-6 lg:py-28" aria-labelledby="section-title">
        <x-section-header :label="$section['label']" :title="$title" level="1" id="section-title">
            {{ $intro }}
        </x-section-header>

        <p class="tribute-steps mt-8" data-rise data-step="{{ $step }}">
            <span class="smallcaps"><span class="sr-only">{{ __('Step :step of :total', ['step' => app(\App\Services\LocaleService::class)->digits(TributeForm::position($step)), 'total' => app(\App\Services\LocaleService::class)->digits(count($steps))]) }}</span><span aria-hidden="true">{{ __('Step :step of :total', ['step' => $typesetter->roman(TributeForm::position($step)), 'total' => $typesetter->roman(count($steps))]) }}</span></span>
            @foreach ($steps as $key => $label)
                @if ($key === $step)
                    <span class="smallcaps tribute-steps__current" aria-current="step">{{ $label }}</span>
                @else
                    <span class="smallcaps">{{ $label }}</span>
                @endif
            @endforeach
        </p>

        <form novalidate method="POST" action="{{ $previewUrl }}" class="form-style-underline mt-12 space-y-10" data-rise>
            @csrf

            <x-form.error-summary :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" :except="[\App\Http\Controllers\TributeController::LIMIT_ERROR, 'consent']" />

            @error(\App\Http\Controllers\TributeController::LIMIT_ERROR)
                <p class="rounded-card border border-danger bg-danger-wash px-4 py-3 text-base font-normal text-danger" role="alert">{{ $message }}</p>
            @enderror

            <x-form.input name="name" :label="$fields['name']['label']" :hint="$fields['name']['hint']" required autocomplete="name" />

            <x-form.select name="relationship" :label="$fields['relationship']['label']" :hint="$fields['relationship']['hint']" :options="$relationships" :placeholder="\App\Constants\FormControls::SELECT_PLACEHOLDER" required />

            <x-form.input name="location" :label="$fields['location']['label']" :hint="$fields['location']['hint']" autocomplete="address-level2" />

            <x-form.textarea name="message" :label="$fields['message']['label']" :hint="$fields['message']['hint']" required :maxlength="$messageMaxLength" rows="8" />

            <x-form.input name="email" :label="$fields['email']['label']" :hint="$fields['email']['hint']" type="email" autocomplete="email" />

            {{-- Honeypot: hidden from people, filled by bots, never validated. --}}
            <div class="form-honeypot" aria-hidden="true">
                <label for="{{ \App\Http\Requests\StoreTributeRequest::HONEYPOT }}">{{ __('Website') }}</label>
                <input type="text" id="{{ \App\Http\Requests\StoreTributeRequest::HONEYPOT }}" name="{{ \App\Http\Requests\StoreTributeRequest::HONEYPOT }}" tabindex="-1" autocomplete="off" value="">
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <x-button>{{ $copy['preview'] }}</x-button>
                <x-button variant="quiet" :href="$indexUrl">{{ $copy['back'] }}</x-button>
            </div>
        </form>
    </section>
</x-layouts.public>
