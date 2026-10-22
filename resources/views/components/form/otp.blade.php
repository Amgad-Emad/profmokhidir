{{--
    A one-time code as six boxes (App\Constants\AuthScreens::CODE_LENGTH), each in the shared
    frame: typing advances, Backspace steps back, arrows move, a pasted or autofilled code
    fills the row. The boxes carry the value into one hidden field named `name`; without
    JavaScript a single plain field takes their place. Extra attributes for the hidden field
    (an Alpine disabled binding) go in `hidden`. Alpine component `formOtp` (forms.js).
--}}
@props([
    'name' => 'code',
    'label' => 'Authentication code',
    'hint' => null,
    'length' => \App\Constants\AuthScreens::CODE_LENGTH,
    'autofocus' => true,
    'required' => true,
    'hidden' => [],
    'bag' => 'default',
    'error' => null,
])
@inject('forms', 'App\Services\FormFieldService')
@php
    $field = $forms->describe(['error' => $error, 'name' => $name, 'id' => $name, 'label' => $label, 'hint' => $hint, 'required' => $required, 'bag' => $bag], $errors ?? null);
    $message = strtr(\App\Constants\FormControls::MESSAGE_CODE, [':length' => (string) $length]);
@endphp
<x-form.field as="fieldset" :field="$field" :class="$attributes->get('class')" x-data="formOtp({ length: {{ (int) $length }} })" data-otp>
    <noscript><style>.form-otp { display: none; }</style></noscript>
    <div class="form-otp" x-on:paste="paste($event)">
        @for ($i = 0; $i < $length; $i++)
            @php
                $box = new \Illuminate\View\ComponentAttributeBag(array_filter([
                    'type' => 'text',
                    'id' => $name.'-'.($i + 1),
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]*',
                    'maxlength' => $i === 0 ? $length : 1,
                    'autocomplete' => $i === 0 ? \App\Constants\AuthScreens::AUTOCOMPLETE_ONE_TIME_CODE : 'off',
                    'aria-label' => 'Digit '.($i + 1).' of '.$length,
                    'aria-invalid' => $field['invalid'] ? 'true' : null,
                    'autofocus' => $i === 0 && $autofocus,
                    'placeholder' => \App\Constants\FormControls::BLANK_PLACEHOLDER,
                    'x-ref' => 'box'.$i,
                    'x-on:input' => 'input('.$i.', $event)',
                    'x-on:keydown' => 'keydown('.$i.', $event)',
                    'x-on:focus' => 'select($event)',
                ], fn ($value): bool => $value !== null && $value !== false));
            @endphp
            <div class="form-control"><input class="form-control__input" {{ $box }}></div>
        @endfor
    </div>
    @php $hiddenBag = new \Illuminate\View\ComponentAttributeBag($hidden + ['data-msg-missing' => $message]); @endphp
    <input type="hidden" name="{{ $name }}" x-ref="value" value="" {{ $hiddenBag }}>
    <noscript>
        <div class="form-control mt-3"><input type="text" id="{{ $name }}" name="{{ $name }}" class="form-control__input" inputmode="numeric" pattern="[0-9]*" maxlength="{{ $length }}" autocomplete="{{ \App\Constants\AuthScreens::AUTOCOMPLETE_ONE_TIME_CODE }}" aria-label="{{ $label }}"></div>
    </noscript>
</x-form.field>
