{{-- Brand typefaces from Google Fonts. Preconnect first, then the stylesheet with display=swap. --}}
@foreach (\App\Constants\Typography::preconnects() as $origin)
    <link rel="preconnect" href="{{ $origin['href'] }}"{!! $origin['crossorigin'] ? ' crossorigin' : '' !!}>
@endforeach
<link rel="stylesheet" href="{{ \App\Constants\Typography::GOOGLE_FONTS_URL }}">
