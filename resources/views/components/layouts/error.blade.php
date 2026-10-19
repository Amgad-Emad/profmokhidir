{{--
    Error pages (resources/views/errors/{code}.blade.php): evergreen with the soft brass glow,
    the MK mark in its ring, a short Cormorant line and a pill button home. No scripts, no
    navigation, never indexed. `code` selects the copy in App\Constants\ErrorPages.
--}}
@props(['code'])
@php
    use App\Constants\ErrorPages;
    use App\Constants\Seo;

    $code = (int) $code;
    $page = ErrorPages::get($code);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page['title'] }}{{ Seo::TITLE_SEPARATOR }}{{ Seo::SITE_NAME }}</title>
    <meta name="robots" content="{{ Seo::ROBOTS_NOINDEX }}">
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css'])
</head>
<body class="error-page glow-brass on-dark min-h-screen bg-evergreen text-champagne">
    <main id="main" class="mx-auto flex min-h-screen w-full max-w-3xl flex-col items-center justify-center gap-8 px-4 py-20 text-center sm:px-6" data-error-code="{{ $code }}">
        <x-brand.mark :size="96" />
        <p class="t-label text-brass">{{ ErrorPages::LABEL_PREFIX }} {{ $code }}</p>
        <h1 class="t-heading text-ivory">{{ $page['title'] }}</h1>
        <p class="t-quote max-w-xl">{{ $page['line'] }}</p>
        <a href="{{ route('home') }}" class="btn-pill-filled mt-4">{{ ErrorPages::HOME_LABEL }}</a>
    </main>
</body>
</html>
