{{--
    A password field: a show/hide button inside the frame (a pressed toggle with a constant
    name), a Caps Lock warning announced politely, and, with `strength`, a length-only strength
    line (the hint names the lengths, a hairline fills as the passphrase grows, the word beside
    it is read out). `autocomplete` is current-password to sign in and new-password to choose
    one. Alpine component `formPassword` (resources/js/forms.js).
--}}
@props([
    'name' => 'password',
    'model' => null,
    'label' => null,
    'autocomplete' => \App\Constants\AuthScreens::AUTOCOMPLETE_CURRENT_PASSWORD,
    'required' => true,
    'strength' => false,
    'hint' => null,
    'id' => null,
    'bag' => 'default',
    'error' => null,
])
@php
    use App\Constants\AuthScreens;
    use App\Constants\FormControls;

    $label ??= __('Password');
    $hint ??= $strength ? __(AuthScreens::PASSWORD_HINT) : null;
    $fieldId = $id ?? app(\App\Services\FormFieldService::class)->idFor($model, $name);
    $strengthId = $fieldId.'-strength';
@endphp
<div {{ $attributes->only('class')->class(['form-password']) }} x-data="formPassword(@js(['levels' => AuthScreens::passwordStrengthLevels(), 'warning' => __(FormControls::CAPS_LOCK)]))">
    <x-form.input
        :name="$name"
        :model="$model"
        :label="$label"
        type="password"
        :required="$required"
        :autocomplete="$autocomplete"
        :hint="$hint"
        :id="$id"
        :bag="$bag"
        :error="$error"
        spellcheck="false"
        autocapitalize="off"
        :control="[
            'x-bind:type' => 'type',
            'x-on:keydown' => 'key($event)',
            'x-on:keyup' => 'key($event)',
            'x-on:blur' => 'blur()',
            'x-on:input' => 'input($event)',
        ]"
        {{ $attributes->except(['class']) }}
    >
        <x-slot:action>
            <button type="button" class="form-control__action" x-on:click="toggle()" x-bind:aria-pressed="visible ? 'true' : 'false'" aria-pressed="false" aria-label="{{ __(FormControls::SHOW_PASSWORD) }}" aria-controls="{{ $fieldId }}">
                <x-icon name="eye" class="size-5" x-show="!visible" />
                <x-icon name="eye-off" class="size-5" x-show="visible" x-cloak />
            </button>
        </x-slot:action>
    </x-form.input>

    <p class="form-message form-note" role="status" aria-live="polite" x-text="capsWarning" data-caps-warning></p>

    @if ($strength)
        <div class="form-strength" data-password-strength>
            <span class="form-strength__track" aria-hidden="true"><span class="form-strength__bar" x-bind:style="'--strength: ' + strength"></span></span>
            <span id="{{ $strengthId }}" class="t-label text-accent-text" aria-live="polite" x-text="strengthLabel"></span>
        </div>
    @endif
</div>
