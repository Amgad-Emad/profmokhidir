{{--
    The appearance control: a sun/moon hairline icon button (the sun shows in the light scheme, the
    moon in the dark) opening a small menu of three radio items, System, Light and Dark. Plain markup
    driven by resources/js/theme.js (no Alpine, so it works in every layout); without script it is
    inert and the account page offers the same choice as a plain form. Inherits the surrounding ink,
    so it sits on the glass masthead and on an ivory bar alike.
--}}
@use('App\Constants\ThemeSettings')
@props(['id' => null])
@inject('themes', 'App\Services\ThemeService')
@php
    $theme = $themes->current(request());
    $options = $themes->options($theme);
    $id ??= 'theme-switch-'.uniqid();
    $menuId = $id.'-menu';
@endphp
<div {{ $attributes->class(['theme-switch']) }} data-theme-switch data-theme-endpoint="{{ route(ThemeSettings::ROUTE) }}">
    <button type="button" id="{{ $id }}" class="theme-switch__button" aria-haspopup="menu" aria-expanded="false" aria-controls="{{ $menuId }}" data-theme-switch-button>
        <span class="sr-only">{{ ThemeSettings::CONTROL_LABEL }}</span>
        <x-icon name="theme-light" class="theme-switch__icon theme-switch__icon--light size-5" />
        <x-icon name="theme-dark" class="theme-switch__icon theme-switch__icon--dark size-5" />
    </button>
    <div id="{{ $menuId }}" class="theme-switch__menu" role="menu" aria-labelledby="{{ $id }}" hidden data-theme-switch-menu>
        <p class="t-label theme-switch__heading" aria-hidden="true">{{ ThemeSettings::LABEL }}</p>
        @foreach ($options as $option)
            <button type="button" role="menuitemradio" class="theme-switch__option" aria-checked="{{ $option['current'] ? 'true' : 'false' }}" tabindex="-1" data-theme-option="{{ $option['value'] }}">
                <x-icon :name="$option['icon']" class="theme-switch__option-icon size-4" />
                <span class="theme-switch__option-text">
                    <span class="theme-switch__option-label">{{ $option['label'] }}</span>
                    <span class="theme-switch__option-description">{{ $option['description'] }}</span>
                </span>
                <x-icon name="check" class="theme-switch__option-check size-4" />
            </button>
        @endforeach
    </div>
</div>
