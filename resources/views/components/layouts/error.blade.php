{{--
    Error pages (resources/views/errors/{code}.blade.php): evergreen with the soft brass glow,
    the MK mark in its ring, a short Cormorant line, a search box that needs no script, the
    chapters of the site and a pill button home. Never indexed. `code` selects the copy in
    Admin › Pages › Error pages (App\Constants\ErrorPages is the default). The chapter list comes
    from the section constants with the names from Header & footer; PageContentService falls back
    to the built-in copy, so the page renders even when the database is unreachable.
--}}
@props(['code'])
@inject('themes', 'App\Services\ThemeService')
@inject('pageCopy', 'App\Services\PageContentService')
@php
    $theme = $themes->current(request());

    use App\Constants\ErrorPages;
    use App\Constants\Search;
    use App\Constants\Sections;
    use App\Constants\Seo;

    $code = (int) $code;
    $copyCode = ErrorPages::isValid($code) ? $code : ErrorPages::SERVER_ERROR;
    $page = ['title' => $pageCopy->text('errors', 'title_'.$copyCode), 'line' => $pageCopy->text('errors', 'line_'.$copyCode)];
    $chapters = Sections::definitions();
    $names = $pageCopy->keyed('navigation', 'sections');
@endphp
<!DOCTYPE html>
<html lang="{{ \App\Constants\Locales::html(app()->getLocale()) }}" dir="{{ \App\Constants\Locales::dir(app()->getLocale()) }}" {{ $themes->htmlAttributes($theme) }}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page['title'] }}{{ Seo::TITLE_SEPARATOR }}{{ __(Seo::SITE_NAME) }}</title>
    <meta name="robots" content="{{ Seo::ROBOTS_NOINDEX }}">
    <x-theme.head />
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css'])
</head>
<body class="error-page glow-brass on-dark min-h-screen bg-surface text-ink">
    <main id="main" class="mx-auto flex min-h-screen w-full max-w-3xl flex-col items-center justify-center gap-8 px-4 py-20 text-center sm:px-6" data-error-code="{{ $code }}">
        <x-brand.mark :size="96" />
        <p class="t-label text-accent-text">{{ $pageCopy->text('errors', 'label') }} {{ $code }}</p>
        <h1 class="t-heading text-ink-strong">{{ $page['title'] }}</h1>
        <p class="t-quote max-w-xl">{{ $page['line'] }}</p>

        <form novalidate method="get" action="{{ route(Search::ROUTE) }}" role="search" class="search-form search-form--ivory w-full max-w-xl text-start">
            <div class="search-form__row form-style-underline">
                <x-form.input type="search" :name="Search::PARAM" id="error-search" :label="$pageCopy->text('errors', 'search_label')" :optional="false" size="display" class="search-form__field"
                              :placeholder="__(Search::PLACEHOLDER)" autocomplete="off" :maxlength="Search::MAX_LENGTH" />
                <x-button type="submit">{{ __(Search::BUTTON_LABEL) }}</x-button>
            </div>
        </form>

        <nav class="error-page__chapters w-full max-w-xl" aria-label="{{ __('Chapters') }}">
            <p class="t-label text-accent-text">{{ $pageCopy->text('errors', 'chapters_label') }}</p>
            <ol class="error-page__chapter-list mt-4">
                @foreach ($chapters as $key => $section)
                    <li><a href="{{ route($section['route']) }}" class="error-page__chapter link-draw smallcaps">{{ $names[$key]['label'] ?? __($section['label']) }}</a></li>
                @endforeach
            </ol>
        </nav>

        <x-button variant="primary" :href="route('home')" class="mt-4">{{ $pageCopy->text('errors', 'home_button') }}</x-button>
    </main>
</body>
</html>
