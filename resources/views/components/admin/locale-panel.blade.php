{{--
    One language of <x-admin.locale-tabs> (`tabs` is the tabs' id): a tab panel written in that
    language and direction, holding the fields on the form grid. Only the default language is shown
    before a tab is chosen. A translation panel opens with a note that its fields are optional and
    fall back to English.
--}}
@props(['tabs', 'locale'])
@use('App\Constants\Locales')
@php
    $isDefault = $locale === Locales::DEFAULT;
    // The panel of the dashboard's language is the one open at first (see x-admin.locale-tabs).
    $isStart = $locale === (Locales::isValid(app()->getLocale()) ? app()->getLocale() : Locales::DEFAULT);
@endphp
<div role="tabpanel" id="{{ $tabs }}-panel-{{ $locale }}" aria-labelledby="{{ $tabs }}-tab-{{ $locale }}" data-content-lang="{{ $locale }}"
     {{ $attributes->class(['locale-tabs__panel form-grid']) }}
     x-show="active === '{{ $locale }}'" @unless ($isStart) style="display: none" @endunless data-locale-panel="{{ $locale }}">
    @unless ($isDefault)
        <p class="form-hint" lang="{{ Locales::html(app()->getLocale()) }}" dir="{{ Locales::dir(app()->getLocale()) }}" data-translation-note>{{ __('Optional. Anything left empty here shows the English text on Arabic pages.') }}</p>
    @endunless
    {{ $slot }}
</div>
