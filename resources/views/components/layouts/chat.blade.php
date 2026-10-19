{{--
    Community chat layout: the one public-facing layout (with /admin) that loads Livewire.
    Glassy evergreen bar with the lockup and the member's links above an ivory page.
    Chat pages are private, so they are never indexed.
--}}
@props(['title' => 'Community chat'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="{{ \App\Constants\Seo::ROBOTS_NOINDEX }}">
    <title>{{ $title }} · {{ \App\Constants\Seo::SITE_NAME }}</title>
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/chat.js'])
    @livewireStyles
</head>
<body class="chat-body bg-ivory text-evergreen">
    <a href="#main" class="skip-link">Skip to content</a>

    <header class="chat-header glass-header on-dark fixed inset-x-0 top-0 z-40 text-champagne">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <x-brand.lockup variant="horizontal" label-tone="champagne" :href="route('home')" class="shrink-0" />
                <span class="t-label hidden text-champagne sm:inline">Community</span>
            </div>
            <nav aria-label="Member">
                <ul class="flex items-center gap-5">
                    <li><a href="{{ route(\App\Services\CommunityPageService::ROUTE_INDEX) }}" class="nav-link t-label">Community</a></li>
                    <li class="hidden sm:block"><a href="{{ route(\App\Constants\AdminNavigation::ACCOUNT_ROUTE) }}" class="nav-link t-label">{{ auth()->user()->name }}</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link t-label">Sign out</button>
                        </form>
                    </li>
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
