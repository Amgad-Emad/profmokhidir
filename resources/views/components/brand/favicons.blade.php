{{-- Favicons, touch icons and the web app manifest from the logo artwork (App\Constants\BrandAssets, files at the root of public/). --}}
@use('App\Constants\BrandAssets')
@use('App\Constants\Brand')
<link rel="icon" href="{{ asset(BrandAssets::FAVICON_ICO) }}" sizes="48x48">
<link rel="icon" href="{{ asset(BrandAssets::FAVICON_SVG) }}" type="image/svg+xml">
<link rel="icon" href="{{ asset(BrandAssets::FAVICON_32) }}" sizes="32x32" type="image/png">
<link rel="icon" href="{{ asset(BrandAssets::FAVICON_16) }}" sizes="16x16" type="image/png">
<link rel="apple-touch-icon" href="{{ asset(BrandAssets::APPLE_TOUCH_ICON) }}" sizes="180x180">
<link rel="mask-icon" href="{{ asset(BrandAssets::SAFARI_PINNED_TAB) }}" color="{{ Brand::EVERGREEN }}">
<link rel="manifest" href="{{ asset(BrandAssets::MANIFEST) }}">
{{-- The browser chrome colour follows the theme: <x-theme.head> prints one theme-color meta per scheme. --}}
