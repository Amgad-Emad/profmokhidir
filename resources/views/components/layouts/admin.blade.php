{{--
    Admin dashboard layout: evergreen sidebar (collapsible below lg), main area on linen.
    The only layout (with /community/chat) that loads Livewire assets. Alpine comes bundled
    with Livewire, so the sidebar toggle needs no extra script.
--}}
@props(['title' => 'Dashboard', 'eyebrow' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Admin · {{ \App\Constants\Seo::SITE_NAME }}</title>
    <meta name="robots" content="{{ \App\Constants\Seo::ROBOTS_NOINDEX }}">
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-linen text-evergreen">
    <x-duotone-filter />
    <a href="#main" class="skip-link">Skip to content</a>

    <div class="admin-shell lg:flex lg:min-h-screen" x-data="{ open: false }" x-on:keydown.escape.window="open = false">
        {{-- Mobile top bar --}}
        <header class="on-dark sticky top-0 z-30 flex h-16 items-center justify-between bg-evergreen px-4 text-champagne lg:hidden">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 no-underline hover:no-underline">
                <x-brand.mark :size="28" decorative />
                <span class="t-label text-brass">Admin</span>
            </a>
            <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-champagne text-champagne hover:border-ivory hover:text-ivory"
                    x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-controls="admin-sidebar">
                <span class="sr-only" x-text="open ? 'Close menu' : 'Open menu'">Open menu</span>
                <x-icon name="menu" x-show="!open" />
                <x-icon name="close" x-show="open" x-cloak />
            </button>
        </header>

        {{-- Backdrop on mobile --}}
        <div class="fixed inset-0 z-30 bg-evergreen/60 lg:hidden" x-show="open" x-cloak x-on:click="open = false" aria-hidden="true"></div>

        <aside id="admin-sidebar"
               class="admin-sidebar on-dark fixed inset-y-0 left-0 z-40 w-72 -translate-x-full bg-evergreen text-champagne transition-transform duration-300 ease-out motion-reduce:transition-none lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shrink-0"
               x-bind:class="{ '-translate-x-full': !open, 'translate-x-0': open }"
               x-on:click.away="open = false"
               x-trap.noscroll="open">
            <x-admin.sidebar />
        </aside>

        <div class="min-w-0 flex-1">
            <header class="hidden h-16 items-center justify-end border-b border-stone bg-ivory px-8 lg:flex">
                <a href="{{ route(\App\Constants\AdminNavigation::ACCOUNT_ROUTE) }}" class="flex items-center gap-2 text-sm text-ash hover:text-evergreen">
                    <x-icon name="user" class="size-4" />
                    <span>{{ auth()->user()->name }}</span>
                </a>
            </header>

            <main id="main" tabindex="-1" class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-10 lg:py-12">
                @if (session('status') && ! in_array(session('status'), \App\Constants\AuthStatus::all(), true))
                    <x-alert tone="cypress" class="mb-8">{{ session('status') }}</x-alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
