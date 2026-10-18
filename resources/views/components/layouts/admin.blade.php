{{-- Admin dashboard layout. The only place (with /community/chat) that loads Livewire assets. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · Admin · {{ config('app.name') }}</title>
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-linen text-evergreen">
    <x-duotone-filter />
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-ivory focus:px-5 focus:py-2">Skip to content</a>

    <header class="on-dark bg-evergreen text-champagne">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                <x-mark :size="28" />
                <span class="t-label text-brass">Admin</span>
            </a>
            <div class="flex items-center gap-4 text-sm">
                <span>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-full border border-champagne px-4 py-1.5 text-sm hover:border-ivory hover:text-ivory">Sign out</button>
                </form>
            </div>
        </div>
    </header>

    <main id="main" class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
