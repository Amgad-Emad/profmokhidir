{{-- Auth pages (Fortify). Plain Blade, no Livewire. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Sign in' }} · {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="brass-glow on-dark min-h-screen bg-evergreen text-champagne">
    <main class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-4 py-16 sm:px-6">
        <div class="mb-10 flex flex-col items-center text-center">
            <x-mark :size="64" />
            <p class="font-display text-2xl leading-tight">Professor<br>Mohamed Osman Khidir</p>
        </div>

        <section class="rounded-card bg-ivory p-8 text-evergreen shadow-sm" aria-labelledby="page-title">
            <h1 id="page-title" class="text-heading leading-tight">{{ $title ?? 'Sign in' }}</h1>
            @isset($intro)
                <p class="mt-3 text-ash">{{ $intro }}</p>
            @endisset

            @if (session('status'))
                <p class="mt-4 rounded-card border border-stone bg-linen px-4 py-3 text-sm" role="status">{{ session('status') }}</p>
            @endif

            <div class="mt-8">
                {{ $slot }}
            </div>
        </section>
    </main>
</body>
</html>
