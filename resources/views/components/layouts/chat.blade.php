{{--
    Community chat layout: the one public-facing layout (with /admin) that loads Livewire.
    A slim glassy evergreen bar with the lockup and the member's links above the
    correspondence room, a fixed-height app shell that fills the rest of the
    viewport: the document never scrolls, only the message list, the side panel
    and the composer's field do, and the composer stays above the keyboard. Chat pages are
    private, so they are never indexed. KaTeX is fetched by the chat script only
    when a message contains maths.
--}}
@props(['title' => 'Community chat'])
@inject('themes', 'App\Services\ThemeService')
@php $theme = $themes->current(request()); @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {{ $themes->htmlAttributes($theme) }}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, interactive-widget=resizes-content">
    <meta name="robots" content="{{ \App\Constants\Seo::ROBOTS_NOINDEX }}">
    <title>{{ $title }} · {{ \App\Constants\Seo::SITE_NAME }}</title>
    <x-theme.head light="evergreen" dark="cypress" />
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/chat.js'])
    @livewireStyles
</head>
<body class="chat-body bg-surface text-ink">
    <a href="#main" class="skip-link">Skip to content</a>

    <header class="chat-header glass-header on-dark fixed inset-x-0 top-0 z-40 text-ink">
        <div class="flex h-16 items-center justify-between gap-6 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <x-brand.lockup variant="horizontal" label-tone="champagne" :href="route('home')" class="chat-header__lockup shrink-0" compact />
                <span class="chat-header__rule hidden sm:block" aria-hidden="true"></span>
                <a href="{{ route(\App\Services\CommunityPageService::ROUTE_INDEX) }}" class="nav-link t-label hidden sm:inline" data-chat-header-label>{{ \App\Constants\ChatCopy::HEADER_LABEL }}</a>
            </div>
            <div class="flex items-center gap-3 sm:gap-4">
                <x-theme.switch id="theme-switch-chat" />
                <x-chat.member-menu :user="auth()->user()" />
            </div>
        </div>
    </header>

    <main id="main" tabindex="-1" class="pt-16">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
