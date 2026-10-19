{{--
    Auth pages (Fortify) and other signed-out screens: ivory page, the stacked lockup above
    a centred linen card. Plain Blade + Alpine, no Livewire.
--}}
@props(['title' => 'Sign in', 'intro' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ivory text-evergreen">
    <main id="main" class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-4 py-16 sm:px-6">
        <div class="mb-10 flex justify-center">
            <x-brand.lockup variant="stacked" tone="evergreen" :href="route('home')" />
        </div>

        <section class="auth-card rounded-card border border-stone bg-linen p-8 sm:p-10" aria-labelledby="page-title">
            <h1 id="page-title" class="font-display text-4xl font-light leading-tight">{{ $title }}</h1>
            @if ($intro)
                <p class="mt-3 text-base font-light text-ash">{{ $intro }}</p>
            @endif

            @if (session('status'))
                @php $statusMessage = \App\Constants\AuthStatus::message(session('status')) ?? session('status'); @endphp
                <x-alert :tone="\App\Constants\AuthStatus::isValid(session('status')) ? \App\Constants\AuthStatus::tone(session('status')) : \App\Constants\StatusTone::Cypress" class="mt-6">{{ $statusMessage }}</x-alert>
            @endif

            <div class="mt-8">
                {{ $slot }}
            </div>
        </section>

        <p class="mt-8 text-center text-sm font-light text-ash">
            <a href="{{ route('home') }}" class="text-bronze underline-offset-4 hover:underline">Back to the website</a>
        </p>
    </main>
</body>
</html>
