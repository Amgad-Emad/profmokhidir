{{--
    Theme plumbing for <head>, once per layout before @vite: the CSRF token (the control saves with
    fetch), the theme-color metas for both schemes and the inline script that settles the preference
    before the first paint. The server prints data-theme / data-theme-preference on <html> from the
    account or the cookie (App\Services\ThemeService::htmlAttributes); the script only steps in for a
    guest whose cookie is gone but whose localStorage still remembers, and mirrors the choice back to
    localStorage. `light` and `dark` name the brand colour of the browser chrome in each scheme.
--}}
@use('App\Constants\Theme')
@use('App\Constants\ThemeSettings')
@props(['light' => 'evergreen', 'dark' => 'evergreen'])
@inject('themes', 'App\Services\ThemeService')
@php
    $theme = $themes->current(request());
    $colours = $themes->themeColours($light, $dark);
    $signedIn = auth()->check();
@endphp
<meta name="csrf-token" content="{{ csrf_token() }}">
@foreach (['light', 'dark'] as $scheme)
    <meta name="{{ ThemeSettings::META_THEME_COLOR }}" content="{{ $theme->isExplicit() ? $colours[$theme->value] : $colours[$scheme] }}" media="(prefers-color-scheme: {{ $scheme }})" data-light="{{ $colours['light'] }}" data-dark="{{ $colours['dark'] }}">
@endforeach
<script data-theme-boot>
    (function () {
        var html = document.documentElement, key = @js(ThemeSettings::STORAGE_KEY), attribute = @js(ThemeSettings::ATTRIBUTE), preference = @js(ThemeSettings::PREFERENCE_ATTRIBUTE);
        var valid = @js(Theme::values()), system = @js(Theme::System->value), current = html.getAttribute(preference) || system;
        var cookie = (document.cookie.match(new RegExp('(?:^|;\\s*)' + @js(ThemeSettings::COOKIE) + '=([a-z]+)')) || [])[1];
        var stored = null;
        try { stored = localStorage.getItem(key); } catch (error) {}
        if (!@js($signedIn) && !cookie && stored && valid.indexOf(stored) !== -1 && stored !== current) {
            current = stored;
            html.setAttribute(preference, current);
            if (current === system) { html.removeAttribute(attribute); } else { html.setAttribute(attribute, current); }
            document.cookie = @js(ThemeSettings::COOKIE) + '=' + current + ';path=/;max-age=' + @js(ThemeSettings::COOKIE_MINUTES * 60) + ';samesite=lax';
        }
        try { localStorage.setItem(key, current); } catch (error) {}
    })();
</script>
