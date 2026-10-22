{{--
    The authenticator code as six separate digit boxes (App\Constants\AuthScreens::CODE_LENGTH):
    typing advances to the next box, Backspace steps back, a pasted or autofilled code fills the
    row. The boxes carry the value into one hidden field named `name`; without JavaScript a plain
    field takes its place. Extra attributes for the hidden field (an Alpine disabled binding) go
    in `hidden`.
--}}
@props([
    'name' => 'code',
    'label' => 'Authentication code',
    'length' => \App\Constants\AuthScreens::CODE_LENGTH,
    'autofocus' => true,
    'hidden' => [],
])
@php
    $hasError = $errors->has($name);
    $errorId = $name.'-error';
@endphp
<div {{ $attributes->class(['code-field', 'field--invalid' => $hasError]) }} x-data="authCode({ length: {{ (int) $length }} })">
    <fieldset class="min-w-0 border-0 p-0" @if ($hasError) aria-describedby="{{ $errorId }}" @endif>
        <legend class="t-label mb-2 text-accent-text">{{ $label }}</legend>
        <noscript><style>.code-field__boxes { display: none; }</style></noscript>
        <div class="code-field__boxes" x-on:paste="paste($event)">
            @for ($i = 0; $i < $length; $i++)
                @php
                    $boxBag = new \Illuminate\View\ComponentAttributeBag(array_filter([
                        'inputmode' => 'numeric',
                        'pattern' => '[0-9]*',
                        'maxlength' => $i === 0 ? $length : 1,
                        'autocomplete' => $i === 0 ? \App\Constants\AuthScreens::AUTOCOMPLETE_ONE_TIME_CODE : 'off',
                        'aria-label' => 'Digit '.($i + 1).' of '.$length,
                        'aria-invalid' => $hasError ? 'true' : null,
                        'autofocus' => $i === 0 && $autofocus,
                        'x-ref' => 'box'.$i,
                        'x-on:input' => 'input('.$i.', $event)',
                        'x-on:keydown' => 'keydown('.$i.', $event)',
                        'x-on:focus' => 'select($event)',
                    ], fn ($value): bool => $value !== null && $value !== false));
                @endphp
                <input type="text" id="{{ $name }}-{{ $i + 1 }}" class="field__input code-field__box" {{ $boxBag }}>
            @endfor
        </div>
        @php $hiddenBag = new \Illuminate\View\ComponentAttributeBag($hidden); @endphp
        <input type="hidden" name="{{ $name }}" x-ref="value" value=""{!! $hiddenBag->isNotEmpty() ? ' '.$hiddenBag : '' !!}>
        <noscript>
            <input type="text" id="{{ $name }}" name="{{ $name }}" class="field__input" inputmode="numeric" pattern="[0-9]*" maxlength="{{ $length }}" autocomplete="{{ \App\Constants\AuthScreens::AUTOCOMPLETE_ONE_TIME_CODE }}" aria-label="{{ $label }}">
        </noscript>
    </fieldset>
    @error($name)
        <p id="{{ $errorId }}" class="field__error" role="alert"><x-icon name="warning" class="mt-0.5 size-4 shrink-0" /><span>{{ $message }}</span></p>
    @enderror
</div>
