{{--
    The language switcher: one small link naming the other language in its own script ("العربية" on
    English pages, "English" on Arabic ones). It stores the choice (cookie, and the account when
    signed in) and opens the same page in that language, keeping the query string; resources/js/i18n.js
    adds the current #anchor on the way. On pages that exist in one form (admin, chat, sign-in) it
    reloads the same address in the other language. Inherits the surrounding ink, like the theme toggle.
--}}
@use('App\Constants\Locales')
@props(['variant' => 'pill'])
@inject('locales', 'App\Services\LocaleService')
@php
    $current = app()->getLocale();
    $other = Locales::other($current);
    $href = $locales->switchUrl($other, $locales->alternateUrl(request(), $other));
@endphp
<a href="{{ $href }}" {{ $attributes->class(['locale-switch', 'locale-switch--'.$variant]) }} lang="{{ Locales::html($other) }}" dir="{{ Locales::dir($other) }}" hreflang="{{ Locales::html($other) }}" rel="alternate nofollow" data-locale-switch>
    <span class="sr-only" lang="{{ Locales::html($current) }}" dir="{{ Locales::dir($current) }}">{{ __('Read this page in') }}</span>
    {{ Locales::native($other) }}
</a>
