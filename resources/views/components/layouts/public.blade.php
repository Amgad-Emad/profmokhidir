{{-- Public layout: plain Blade + Tailwind + Alpine. Never load Livewire here. `image` is the absolute URL of a static share card (see App\Constants\SocialCards). --}}
@props(['title' => null, 'description' => null, 'image' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @if ($image)
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ $title ?? config('app.name') }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $image }}">
        <meta property="og:image:width" content="{{ \App\Constants\SocialCards::WIDTH }}">
        <meta property="og:image:height" content="{{ \App\Constants\SocialCards::HEIGHT }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $image }}">
    @endif
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ivory text-evergreen">
    <x-duotone-filter />
    <a href="#main" class="skip-link">Skip to content</a>

    <x-site.header />

    <main id="main" tabindex="-1" class="pt-20">
        {{ $slot }}
    </main>

    <x-site.footer />
</body>
</html>
