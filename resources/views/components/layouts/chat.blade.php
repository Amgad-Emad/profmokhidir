{{--
    Community chat layout: the one public-facing layout (with /admin) that loads Livewire.
    A slim glassy evergreen bar with the lockup and the member's links above the
    correspondence room, which fills the rest of the viewport (the conversation
    scrolls inside it and the composer stays above the keyboard). Chat pages are
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
                <x-brand.lockup variant="horizontal" label-tone="champagne" :href="route('home')" class="shrink-0" compact />
                <span class="t-label hidden text-ink lg:inline">{{ \App\Constants\ChatCopy::TITLE }}</span>
            </div>
            <nav aria-label="Member">
                <ul class="flex items-center gap-5">
                    <li class="hidden sm:block"><a href="{{ route(\App\Services\CommunityPageService::ROUTE_INDEX) }}" class="nav-link t-label">Community</a></li>
                    <li class="hidden md:block"><a href="{{ route(\App\Constants\AdminNavigation::ACCOUNT_ROUTE) }}" class="nav-link t-label">{{ auth()->user()->name }}</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link t-label">Sign out</button>
                        </form>
                    </li>
                    <li><x-theme.switch id="theme-switch-chat" /></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="main" tabindex="-1" class="pt-16">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
