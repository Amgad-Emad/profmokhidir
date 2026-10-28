{{-- One colour token: swatch, label, where it is used, whether it is set or follows the others, the picker and hex field, and the reset link. --}}
@use('App\Constants\ThemeTokens')
@use('App\Support\Colour')
@php
    $definition = $tokens[$token];
    $value = $resolved[$mode][$token];
    $isBasic = $definition['group'] === ThemeTokens::GROUP_BASIC;
    $isSet = array_key_exists($token, $explicit);
    $state = match (true) {
        $isBasic => null,
        ! $isSet => __('Follows the basic colours'),
        ($templateMode[$token] ?? null) === $explicit[$token] => __('From the template'),
        default => __('Set by hand'),
    };
    $alpha = Colour::isTranslucent($value) ? substr($value, 7, 2) : '';
    $id = 'token-'.$mode.'-'.$token;
@endphp
<div @class(['token-row', 'token-row--large' => $large]) wire:key="{{ $id }}" data-token="{{ $token }}" data-value="{{ $value }}">
    <span class="token-row__swatch" style="--swatch: {{ $value }}" aria-hidden="true"></span>
    <div class="token-row__text">
        <label class="token-row__label" for="{{ $id }}">{{ __($definition['label']) }}</label>
        <p id="{{ $id }}-description" class="token-row__description">{{ __($definition['description']) }}</p>
        @if ($state)
            <p class="token-row__state" data-state>{{ $state }}</p>
        @endif
    </div>
    <div class="token-row__controls">
        @if ($token === ThemeTokens::MARK_RING)
            <div class="token-row__options" role="group" aria-labelledby="{{ $id }}-description">
                @foreach ($markRingOptions as $option)
                    <button type="button" class="token-row__pick" style="--swatch: {{ $option }}" wire:click="setColour('{{ $token }}', '{{ $option }}')" aria-pressed="{{ $option === $value ? 'true' : 'false' }}" @disabled(! $editable) data-ring-option="{{ $option }}"><span class="sr-only">{{ __('Use :colour', ['colour' => $option]) }}</span></button>
                @endforeach
            </div>
            <input id="{{ $id }}" type="text" class="token-row__hex" value="{{ $value }}" readonly aria-describedby="{{ $id }}-description" dir="ltr">
        @else
            <input type="color" class="token-row__picker" value="{{ substr($value, 0, 7) }}" aria-label="{{ __(':label, colour picker', ['label' => __($definition['label'])]) }}" x-on:input="preview('{{ $token }}', $event.target.value, '{{ $alpha }}')" x-on:change="choose('{{ $token }}', $event.target.value, '{{ $alpha }}')" @disabled(! $editable)>
            <input id="{{ $id }}" type="text" class="token-row__hex" value="{{ $value }}" spellcheck="false" autocomplete="off" maxlength="9" dir="ltr" aria-describedby="{{ $id }}-description {{ $id }}-error" x-on:change="choose('{{ $token }}', $event.target.value, '')" x-on:keydown.enter.prevent="choose('{{ $token }}', $event.target.value, '')" @disabled(! $editable) @error('colour.'.$token) aria-invalid="true" @enderror>
            <x-button type="button" variant="icon" size="round" x-show="eyedropper" x-cloak x-on:click="eyedrop('{{ $token }}', '{{ $alpha }}')" :disabled="! $editable" data-eyedropper><x-icon name="droplet" class="size-4" /><span class="sr-only">{{ __('Pick :label from the screen', ['label' => __($definition['label'])]) }}</span></x-button>
        @endif
        @if ($editable && ! $isBasic && $isSet)
            <x-button type="button" size="sm" variant="quiet" wire:click="resetToken('{{ $token }}')" data-reset-token="{{ $token }}">{{ __('Reset to template') }}<span class="sr-only"> ({{ __($definition['label']) }})</span></x-button>
        @endif
    </div>
    <p id="{{ $id }}-error" class="form-error token-row__error text-sm" role="alert">@error('colour.'.$token){{ $message }}@enderror</p>
    @if ($large && $editable)
        <details class="token-row__picks">
            <summary class="text-sm">{{ __('Colours of this palette') }}</summary>
            <div class="token-row__options">
                @foreach ($quickPicks as $pick)
                    <button type="button" class="token-row__pick" style="--swatch: {{ $pick }}" wire:click="setColour('{{ $token }}', '{{ $pick }}')" aria-pressed="{{ $pick === $value ? 'true' : 'false' }}"><span class="sr-only">{{ __('Use :colour for :label', ['colour' => $pick, 'label' => __($definition['label'])]) }}</span></button>
                @endforeach
            </div>
        </details>
    @endif
</div>
