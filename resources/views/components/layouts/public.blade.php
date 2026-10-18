{{-- Public layout: plain Blade + Tailwind + Alpine. Never load Livewire here. --}}
@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
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
