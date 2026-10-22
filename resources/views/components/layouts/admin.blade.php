{{--
    Admin dashboard layout: the dark sidebar band (collapsible below lg), main area on the sunken
    surface with panels on it. The only layout (with /community/chat) that loads Livewire assets.
    Alpine comes bundled with Livewire, so the sidebar toggle needs no extra script. The theme
    control sits in both top bars; <x-theme.head> carries the CSRF token and the theme metas.
--}}
@props(['title' => 'Dashboard', 'eyebrow' => null])
@inject('themes', 'App\Services\ThemeService')
@php $theme = $themes->current(request()); @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {{ $themes->htmlAttributes($theme) }}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Admin · {{ \App\Constants\Seo::SITE_NAME }}</title>
    <meta name="robots" content="{{ \App\Constants\Seo::ROBOTS_NOINDEX }}">
    <x-theme.head />
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-surface-sunken text-ink tabular-nums">
    <x-duotone-filter />
    <a href="#main" class="skip-link">Skip to content</a>

    <div class="admin-shell lg:flex lg:min-h-screen" x-data="{ open: false }" x-on:keydown.escape.window="open = false">
        {{-- Mobile top bar --}}
        <header class="on-dark sticky top-0 z-30 flex h-16 items-center justify-between bg-surface px-4 text-ink lg:hidden">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 no-underline hover:no-underline">
                <x-brand.mark :size="28" decorative />
                <span class="t-label text-accent-text">Admin</span>
            </a>
            <div class="flex items-center gap-1">
                <x-theme.switch id="theme-switch-admin-bar" />
                <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-ink text-ink hover:border-ink-strong hover:text-ink-strong"
                        x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-controls="admin-sidebar">
                    <span class="sr-only" x-text="open ? 'Close menu' : 'Open menu'">Open menu</span>
                    <x-icon name="menu" x-show="!open" />
                    <x-icon name="close" x-show="open" x-cloak />
                </button>
            </div>
        </header>

        {{-- Backdrop on mobile --}}
        <div class="fixed inset-0 z-30 bg-surface-overlay lg:hidden" x-show="open" x-cloak x-on:click="open = false" aria-hidden="true"></div>

        <aside id="admin-sidebar"
               class="admin-sidebar on-dark fixed inset-y-0 left-0 z-40 w-72 -translate-x-full bg-surface text-ink transition-transform duration-300 ease-out motion-reduce:transition-none lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shrink-0"
               x-bind:class="{ '-translate-x-full': !open, 'translate-x-0': open }"
               x-on:click.away="open = false"
               x-trap.noscroll="open">
            <x-admin.sidebar />
        </aside>

        <div class="min-w-0 flex-1">
            <header class="hidden h-16 items-center justify-end gap-4 border-b border-rule bg-surface-panel px-8 lg:flex">
                <a href="{{ route(\App\Constants\AdminNavigation::ACCOUNT_ROUTE) }}" class="flex items-center gap-2 text-sm text-ink-muted hover:text-ink">
                    <x-icon name="user" class="size-4" />
                    <span>{{ auth()->user()->name }}</span>
                </a>
                <x-theme.switch id="theme-switch-admin" />
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
