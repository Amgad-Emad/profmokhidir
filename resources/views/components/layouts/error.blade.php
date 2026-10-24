{{--
    Error pages (resources/views/errors/{code}.blade.php): evergreen with the soft brass glow,
    the MK mark in its ring, a short Cormorant line, a search box that needs no script, the
    chapters of the site and a pill button home. Never indexed. `code` selects the copy in
    App\Constants\ErrorPages. The chapter list comes from the section constants, so the page
    renders even when the database is unreachable.
--}}
@props(['code'])
@inject('themes', 'App\Services\ThemeService')
@php
    $theme = $themes->current(request());

    use App\Constants\ErrorPages;
    use App\Constants\Search;
    use App\Constants\Sections;
    use App\Constants\Seo;

    $code = (int) $code;
    $page = ErrorPages::get($code);
    $chapters = Sections::definitions();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {{ $themes->htmlAttributes($theme) }}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page['title'] }}{{ Seo::TITLE_SEPARATOR }}{{ Seo::SITE_NAME }}</title>
    <meta name="robots" content="{{ Seo::ROBOTS_NOINDEX }}">
    <x-theme.head />
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css'])
</head>
<body class="error-page glow-brass on-dark min-h-screen bg-surface text-ink">
    <main id="main" class="mx-auto flex min-h-screen w-full max-w-3xl flex-col items-center justify-center gap-8 px-4 py-20 text-center sm:px-6" data-error-code="{{ $code }}">
        <x-brand.mark :size="96" />
        <p class="t-label text-accent-text">{{ ErrorPages::LABEL_PREFIX }} {{ $code }}</p>
        <h1 class="t-heading text-ink-strong">{{ $page['title'] }}</h1>
        <p class="t-quote max-w-xl">{{ $page['line'] }}</p>

        <form novalidate method="get" action="{{ route(Search::ROUTE) }}" role="search" class="search-form search-form--ivory w-full max-w-xl text-left">
            <div class="search-form__row form-style-underline">
                <x-form.input type="search" :name="Search::PARAM" id="error-search" :label="ErrorPages::SEARCH_LABEL" :optional="false" size="display" class="search-form__field"
                              :placeholder="Search::PLACEHOLDER" autocomplete="off" :maxlength="Search::MAX_LENGTH" />
                <x-button type="submit">{{ Search::BUTTON_LABEL }}</x-button>
            </div>
        </form>

        <nav class="error-page__chapters w-full max-w-xl" aria-label="Chapters">
            <p class="t-label text-accent-text">{{ ErrorPages::CHAPTERS_LABEL }}</p>
            <ol class="error-page__chapter-list mt-4">
                @foreach ($chapters as $section)
                    <li><a href="{{ route($section['route']) }}" class="error-page__chapter link-draw smallcaps">{{ $section['label'] }}</a></li>
                @endforeach
            </ol>
        </nav>

        <x-button variant="primary" :href="route('home')" class="mt-4">{{ ErrorPages::HOME_LABEL }}</x-button>
    </main>
</body>
</html>
