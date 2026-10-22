{{--
    A password field in the underlined style with a show/hide toggle (a pressed button with a
    constant accessible name), a Caps Lock warning announced politely, and, with `strength`,
    a length-only strength line: the hint names the lengths, a hairline fills as the passphrase
    grows and the word beside it is read out. Passes `autocomplete` through, so the sign-in
    form says current-password and the reset and signup forms new-password.
--}}
@props([
    'name' => 'password',
    'label' => 'Password',
    'autocomplete' => \App\Constants\AuthScreens::AUTOCOMPLETE_CURRENT_PASSWORD,
    'required' => true,
    'strength' => false,
    'hint' => null,
    'autofocus' => false,
    'id' => null,
    'bag' => 'default',
])
@php
    use App\Constants\AuthScreens;

    $id ??= $name;
    $hint ??= $strength ? AuthScreens::PASSWORD_HINT : null;
    $strengthId = $id.'-strength';
@endphp
<div {{ $attributes->class(['password-field']) }} x-data="authPassword(@js(['levels' => AuthScreens::passwordStrengthLevels(), 'warning' => AuthScreens::CAPS_LOCK_WARNING]))">
    <x-auth.field
        :name="$name"
        :label="$label"
        type="password"
        :required="$required"
        :autocomplete="$autocomplete"
        :hint="$hint"
        :id="$id"
        :autofocus="$autofocus"
        :bag="$bag"
        :input="[
            'x-bind:type' => 'type',
            'x-on:keydown' => 'key($event)',
            'x-on:keyup' => 'key($event)',
            'x-on:blur' => 'blur()',
            'x-on:input' => 'input($event)',
        ]"
    >
        <button type="button" class="field__toggle" x-on:click="toggle()" x-bind:aria-pressed="visible ? 'true' : 'false'" aria-pressed="false" aria-label="{{ AuthScreens::SHOW_PASSWORD }}" aria-controls="{{ $id }}">
            <x-icon name="eye" class="size-5" x-show="!visible" />
            <x-icon name="eye-off" class="size-5" x-show="visible" x-cloak />
        </button>
    </x-auth.field>

    <p class="field__note" role="status" aria-live="polite" x-text="capsWarning"></p>

    @if ($strength)
        <div class="strength" data-password-strength>
            <span class="strength__track" aria-hidden="true"><span class="strength__bar" x-bind:style="'--strength: ' + strength"></span></span>
            <span id="{{ $strengthId }}" class="t-label text-accent-text" aria-live="polite" x-text="strengthLabel"></span>
        </div>
    @endif
</div>
