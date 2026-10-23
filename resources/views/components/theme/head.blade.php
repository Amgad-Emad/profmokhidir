{{--
    Theme plumbing for <head>, once per layout before @vite: the CSRF token (the toggle saves with
    fetch), the theme-color metas for both schemes and the inline script that settles the theme before
    the first paint. The server prints data-theme on <html> once a theme has been chosen (the account,
    else the cookie: App\Services\ThemeService::htmlAttributes); without one, prefers-color-scheme
    decides. The script steps in when the cookie is gone but localStorage still remembers a choice, and
    when either still holds the retired "system": it pins the theme the device resolves to and marks
    <html> so resources/js/theme.js saves it on the account. `light` and `dark` name the brand colour
    of the browser chrome in each scheme.
--}}
@use('App\Constants\Theme')
@use('App\Constants\ThemeSettings')
@props(['light' => 'evergreen', 'dark' => 'evergreen'])
@inject('themes', 'App\Services\ThemeService')
@php
    $theme = $themes->current(request());
    $colours = $themes->themeColours($light, $dark);
@endphp
<meta name="csrf-token" content="{{ csrf_token() }}">
@foreach (['light', 'dark'] as $scheme)
    <meta name="{{ ThemeSettings::META_THEME_COLOR }}" content="{{ $theme !== null ? $colours[$theme->value] : $colours[$scheme] }}" media="(prefers-color-scheme: {{ $scheme }})" data-light="{{ $colours['light'] }}" data-dark="{{ $colours['dark'] }}">
@endforeach
<script data-theme-boot>
    (function () {
        var html = document.documentElement, key = @js(ThemeSettings::STORAGE_KEY), attribute = @js(ThemeSettings::ATTRIBUTE), name = @js(ThemeSettings::COOKIE);
        var valid = @js(Theme::values()), legacy = @js(ThemeSettings::LEGACY_SYSTEM), chosen = html.getAttribute(attribute);
        var cookie = (document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([a-z]+)')) || [])[1];
        var stored = null;
        try { stored = localStorage.getItem(key); } catch (error) {}
        function keep(value) {
            html.setAttribute(attribute, value);
            document.cookie = name + '=' + value + ';path=/;max-age=' + @js(ThemeSettings::COOKIE_MINUTES * 60) + ';samesite=lax';
            try { localStorage.setItem(key, value); } catch (error) {}
        }
        if (valid.indexOf(chosen) !== -1) {
            try { localStorage.setItem(key, chosen); } catch (error) {}
        } else if (cookie === legacy || stored === legacy) {
            keep(window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            html.setAttribute(@js(ThemeSettings::LEGACY_ATTRIBUTE), '');
        } else if (valid.indexOf(stored) !== -1) {
            keep(stored);
        }
    })();
</script>
