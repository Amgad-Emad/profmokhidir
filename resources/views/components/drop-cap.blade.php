{{--
    Opens long prose with a three-line Cormorant initial on its first paragraph (CSS
    ::first-letter, so it works on HTML from the database). `tone` is the ink:
    "evergreen" on ivory (default) or "ivory" on evergreen, where the initial is champagne.
    Arabic letters join, so on Arabic pages the first word of the first paragraph is wrapped
    instead and set in the Arabic display face at 700 (resources/css/locale.css); English text keeps the initial.
--}}
@use('App\Constants\Typesetting')
@props(['tone' => 'evergreen'])
@php
    $content = (string) $slot;
    if (app()->getLocale() === \App\Constants\Typography::ARABIC_LOCALE) {
        $content = preg_replace('/(<p\b[^>]*>\s*)([^\s<]+)/u', '$1<span class="'.Typesetting::DROP_CAP_LEAD_CLASS.'">$2</span>', $content, 1) ?? $content;
    }
@endphp
<div {{ $attributes->class(['drop-cap', 'drop-cap--ivory' => $tone === 'ivory']) }}>{!! $content !!}</div>
