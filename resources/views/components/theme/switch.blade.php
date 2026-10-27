{{--
    The theme toggle: one hairline icon button, Light or Dark, no menu. The moon shows in the light
    theme and the sun in the dark; a click, Enter or Space switches at once (resources/js/theme.js),
    the label names the theme it switches to ("Switch to dark theme") and aria-pressed is true while
    the dark theme shows. It is a one-button form, so without script it still stores the choice and
    comes back. Inherits the surrounding ink, so it sits on the glass masthead and on an ivory bar alike.
--}}
@use('App\Constants\ThemeSettings')
@props(['id' => null])
@inject('themes', 'App\Services\ThemeService')
@php
    $toggle = $themes->toggle($themes->current(request()));
    $id ??= 'theme-switch-'.uniqid();
@endphp
<form novalidate method="POST" action="{{ route(ThemeSettings::ROUTE) }}" {{ $attributes->class(['theme-toggle']) }} data-theme-toggle data-theme-label="{{ ThemeSettings::SWITCH_LABEL }}" data-theme-label-light="{{ \App\Constants\Theme::Dark->switchLabel() }}" data-theme-label-dark="{{ \App\Constants\Theme::Light->switchLabel() }}" data-theme-showing="{{ $toggle['theme'] }}">
    @csrf
    @method('PATCH')
    <button type="submit" id="{{ $id }}" name="{{ ThemeSettings::FIELD }}" value="{{ $toggle['next'] }}" class="theme-toggle__button" aria-label="{{ $toggle['label'] }}" aria-pressed="{{ $toggle['pressed'] }}" data-theme-toggle-button>
        <x-icon name="theme-dark" class="theme-toggle__icon theme-toggle__icon--moon size-5" />
        <x-icon name="theme-light" class="theme-toggle__icon theme-toggle__icon--sun size-5" />
    </button>
</form>
