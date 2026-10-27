{{-- Title, description, canonical, Open Graph, Twitter card and JSON-LD for a public page. --}}
<title>{{ $pageTitle }}</title>
@if ($metaDescription)
    <meta name="description" content="{{ $metaDescription }}">
@endif
@if ($noindex)
    <meta name="robots" content="{{ \App\Constants\Seo::ROBOTS_NOINDEX }}">
@endif
<link rel="canonical" href="{{ $canonicalUrl }}">
@foreach ($alternates as $hreflang => $href)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
@endforeach
@if ($defaultAlternate)
    <link rel="alternate" hreflang="x-default" href="{{ $defaultAlternate }}">
@endif
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $siteName() }}">
<meta property="og:locale" content="{{ $ogLocale }}">
@foreach ($ogAlternateLocales as $alternateLocale)
    <meta property="og:locale:alternate" content="{{ $alternateLocale }}">
@endforeach
<meta property="og:title" content="{{ $pageTitle }}">
@if ($metaDescription)
    <meta property="og:description" content="{{ $metaDescription }}">
@endif
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $imageUrl }}">
<meta property="og:image:width" content="{{ \App\Constants\SocialCards::WIDTH }}">
<meta property="og:image:height" content="{{ \App\Constants\SocialCards::HEIGHT }}">
<meta property="og:image:alt" content="{{ $imageAltText }}">
<meta name="twitter:card" content="{{ \App\Constants\Seo::TWITTER_CARD }}">
<meta name="twitter:title" content="{{ $pageTitle }}">
@if ($metaDescription)
    <meta name="twitter:description" content="{{ $metaDescription }}">
@endif
<meta name="twitter:image" content="{{ $imageUrl }}">
<meta name="twitter:image:alt" content="{{ $imageAltText }}">
@if ($jsonLd)
    <script type="application/ld+json">{!! $jsonLd !!}</script>
@endif
