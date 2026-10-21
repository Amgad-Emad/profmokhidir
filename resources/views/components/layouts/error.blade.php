{{--
    Error pages (resources/views/errors/{code}.blade.php): evergreen with the soft brass glow,
    the MK mark in its ring, a short Cormorant line, a search box that needs no script, the
    chapters of the site and a pill button home. Never indexed. `code` selects the copy in
    App\Constants\ErrorPages. The chapter list comes from the section constants, so the page
    renders even when the database is unreachable.
--}}
@props(['code'])
@php
    use App\Constants\ErrorPages;
    use App\Constants\Search;
    use App\Constants\Sections;
    use App\Constants\Seo;

    $code = (int) $code;
    $page = ErrorPages::get($code);
    $chapters = Sections::definitions();
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

        <form method="get" action="{{ route(Search::ROUTE) }}" role="search" class="search-form search-form--ivory w-full max-w-xl text-left">
            <label for="error-search" class="t-label block text-brass">{{ ErrorPages::SEARCH_LABEL }}</label>
            <div class="search-form__row mt-3">
                <input id="error-search" name="{{ Search::PARAM }}" type="search" placeholder="{{ Search::PLACEHOLDER }}" autocomplete="off" maxlength="{{ Search::MAX_LENGTH }}" class="search-form__input">
                <button type="submit" class="btn-pill-outline">{{ Search::BUTTON_LABEL }}</button>
            </div>
        </form>

        <nav class="error-page__chapters w-full max-w-xl" aria-label="Chapters">
            <p class="t-label text-brass">{{ ErrorPages::CHAPTERS_LABEL }}</p>
            <ol class="error-page__chapter-list mt-4">
                @foreach ($chapters as $section)
                    <li><a href="{{ route($section['route']) }}" class="error-page__chapter link-draw smallcaps">{{ $section['label'] }}</a></li>
                @endforeach
            </ol>
        </nav>

        <a href="{{ route('home') }}" class="btn-pill-filled mt-4">{{ ErrorPages::HOME_LABEL }}</a>
    </main>
</body>
</html>
