@use('App\Constants\Typography')
{{-- Brand typefaces, self-hosted from public/fonts (see resources/css/fonts.css). The display face is preloaded; nothing is fetched from a third party, except on Arabic pages, which add the two Arabic faces from Google Fonts (the documented exception in App\Constants\Typography). --}}
<link rel="preload" href="{{ asset(Typography::path(Typography::PRELOAD_FILE)) }}" as="font" type="font/woff2" crossorigin>
@if (Typography::loadsArabicFaces(app()->getLocale()))
<link rel="preconnect" href="{{ Typography::GOOGLE_FONTS_ORIGIN }}">
<link rel="preconnect" href="{{ Typography::GOOGLE_FONTS_STATIC_ORIGIN }}" crossorigin>
{{-- Not render-blocking: the stylesheet loads alongside the page and applies when it arrives (display=swap keeps text visible meanwhile). --}}
<link rel="preload" href="{{ Typography::ARABIC_STYLESHEET }}" as="style">
<link rel="stylesheet" href="{{ Typography::ARABIC_STYLESHEET }}" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="{{ Typography::ARABIC_STYLESHEET }}"></noscript>
@endif
