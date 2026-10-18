{{-- Public layout: plain Blade + Tailwind + Alpine. Never load Livewire here. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ivory text-evergreen">
    <x-duotone-filter />
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-ivory focus:px-5 focus:py-2 focus:text-evergreen">Skip to content</a>

    <header class="glass-header on-dark fixed inset-x-0 top-0 z-40 text-champagne">
        <nav class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6" aria-label="Primary">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <x-mark :size="36" />
                <span class="font-display text-xl leading-none">Professor <span class="block">Mohamed Osman Khidir</span></span>
            </a>
            {{ $nav ?? '' }}
        </nav>
    </header>

    <main id="main" class="pt-20">
        {{ $slot }}
    </main>

    <footer class="on-dark bg-evergreen text-champagne">
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-12 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex items-center gap-2">
                <x-mark :size="32" />
                <span class="font-display text-lg leading-none">Professor Mohamed Osman Khidir</span>
            </div>
            <p class="t-label text-brass">&copy; {{ date('Y') }} profmokhidir.com</p>
        </div>
    </footer>
</body>
</html>
