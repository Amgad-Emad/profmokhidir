{{--
    Admin dashboard layout. The evergreen sidebar (an on-dark band: a drawer below lg, collapsible
    to icons above it), the top bar (breadcrumbs, the command palette, theme, View site, the user
    menu) and the main area on the sunken surface. Around every screen: the command palette
    (Ctrl/Cmd+K), the toast stack (one live region), the shortcuts sheet ("?") and the
    "Leave without saving?" guard. The only layout (with /community/chat) that loads Livewire;
    Alpine comes bundled with it. `crumb` names the record in the breadcrumb trail (defaults to
    the title). Frame data comes from App\Services\AdminShellService.
--}}
@props(['title' => null, 'eyebrow' => null, 'crumb' => null])
@inject('themes', 'App\Services\ThemeService')
@inject('shell', 'App\Services\AdminShellService')
@php
    $theme = $themes->current(request());
    $user = auth()->user();
    $route = request()->route()?->getName();
    $nav = $shell->sidebar($user, $route);
    $crumbs = $shell->breadcrumbs($route, $crumb ?? $title);
    $flash = session('admin.status');
    $title ??= __('Dashboard');
@endphp
<!DOCTYPE html>
<html lang="{{ \App\Constants\Locales::html(app()->getLocale()) }}" dir="{{ \App\Constants\Locales::dir(app()->getLocale()) }}" class="admin-html" {{ $themes->htmlAttributes($theme) }}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ __('Admin') }} · {{ \App\Constants\Seo::SITE_NAME }}</title>
    <meta name="robots" content="{{ \App\Constants\Seo::ROBOTS_NOINDEX }}">
    <x-theme.head />
    <x-brand.favicons />
    <x-fonts />
    <x-locale.script-strings :bundles="[\App\Constants\ScriptStrings::ADMIN]" />
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body class="admin-body min-h-screen bg-surface-sunken text-ink tabular-nums" data-placeholder-marker="{{ \App\Constants\Placeholders::MARKER }}" data-placeholder-flag="{{ __(\App\Constants\Placeholders::FLAG) }}" data-placeholder-hint="{{ __(\App\Constants\Placeholders::FLAG_HINT) }}">
    <x-duotone-filter />
    <a href="#main" class="skip-link">{{ __('Skip to content') }}</a>

    <div class="admin-shell lg:flex lg:min-h-screen" x-data="adminShell" x-bind:data-collapsed="collapsed ? 'true' : 'false'" data-collapsed="false"
         x-on:keydown.escape.window="open = false">
        {{-- Backdrop of the mobile drawer --}}
        <div class="fixed inset-0 z-30 bg-surface-overlay lg:hidden" x-show="open" x-cloak x-on:click="open = false" aria-hidden="true"></div>

        <aside id="admin-sidebar"
               class="admin-sidebar on-dark fixed inset-y-0 start-0 z-40 -translate-x-full rtl:translate-x-full bg-surface text-ink transition-transform duration-300 ease-out motion-reduce:transition-none lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 rtl:lg:translate-x-0 lg:shrink-0"
               x-bind:class="{ '-translate-x-full rtl:translate-x-full': !open, 'translate-x-0': open }"
               x-trap.noscroll="open"
               aria-label="{{ __('Dashboard menu') }}">
            <x-admin.sidebar :nav="$nav" :user="$user" />
        </aside>

        <div class="admin-frame min-w-0 flex-1">
            <x-admin.topbar :crumbs="$crumbs" :user="$user" />

            <main id="main" tabindex="-1" class="admin-main mx-auto w-full max-w-6xl px-4 pt-8 pb-16 sm:px-6 lg:px-10 lg:pt-10">
                @if (session('status') && ! in_array(session('status'), \App\Constants\AuthStatus::all(), true))
                    <x-alert tone="cypress" class="mb-8">{{ session('status') }}</x-alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-admin.command-palette :entries="$shell->paletteEntries($user)" />
    <x-admin.toasts :initial="is_string($flash) ? $flash : null" />
    <x-admin.shortcuts-sheet />
    <x-admin.leave-guard />

    @livewireScripts
</body>
</html>
