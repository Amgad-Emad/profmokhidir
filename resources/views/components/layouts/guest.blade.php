{{--
    The sign-in screens (Fortify) and the other signed-out pages: a private reading room.
    From 1024px the left half is evergreen with the brass glow, an archive portrait in the
    duotone (decorative, lazy), the epigraph in champagne and its place and year; the right
    half is the page surface with the stacked lockup and the form in a 400px column, no card,
    hierarchy from space and hairlines. Below that the evergreen panel is a short band with the mark
    and the epigraph, and the form follows. `screen` is an App\Constants\AuthScreens key,
    which supplies the title, intro, primary action and busy label (App\Services\AuthPageService);
    pass `title` or `intro` to override, `:intro="false"` to write the intro in the slot,
    and `error-targets` when an error key should link to another id. Plain Blade + Alpine, no Livewire.
--}}
@use('App\Constants\AuthScreens')
@use('App\Constants\AuthStatus')
@use('App\Constants\Seo')
@use('App\Constants\StatusTone')
@use('App\Support\ResponsiveImage')
@props(['screen' => AuthScreens::LOGIN, 'title' => null, 'intro' => null, 'errorTargets' => []])
@inject('authPage', 'App\Services\AuthPageService')
@inject('themes', 'App\Services\ThemeService')
@php
    $theme = $themes->current(request());
    $page = $authPage->layout($screen);
    $title ??= $page['screen']['title'];
    $intro = $intro === false ? null : ($intro ?? $page['screen']['intro']);
    $portrait = $page['portrait'];
    $dimensions = $portrait === null ? null : ResponsiveImage::dimensions($portrait);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {{ $themes->htmlAttributes($theme) }}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ Seo::SITE_NAME }}</title>
    <meta name="robots" content="{{ Seo::ROBOTS_NOINDEX }}">
    <x-theme.head />
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth min-h-screen bg-surface text-ink" data-auth-screen="{{ $screen }}">
    <x-duotone-filter />
    <a href="#main" class="skip-link">Skip to content</a>

    <div class="auth__split">
        <aside class="auth__panel glow-brass on-dark" aria-label="{{ $page['label'] }}">
            {{-- Desktop: the archive print above the epigraph. --}}
            <div class="auth__panel-print">
                @if ($portrait !== null)
                    <figure class="auth__portrait" data-auth-portrait>
                        <img src="{{ ResponsiveImage::largestUrl($portrait) }}"
                             @if (($srcset = ResponsiveImage::srcset($portrait)) !== null) srcset="{{ $srcset }}" sizes="(min-width: 1024px) 44vw, 1px" @endif
                             alt=""
                             @if ($dimensions !== null) width="{{ $dimensions[0] }}" height="{{ $dimensions[1] }}" @endif
                             class="photo-archive"
                             loading="lazy"
                             decoding="async">
                    </figure>
                    @if ($page['caption'])
                        <p class="t-label text-accent-text" data-auth-caption>{{ $page['caption'] }}</p>
                    @endif
                @else
                    <div class="auth__portrait" aria-hidden="true"></div>
                @endif
            </div>

            {{-- Mobile and tablet: the mark in the short band. --}}
            <div class="auth__panel-band">
                <x-brand.lockup variant="horizontal" tone="ivory" :href="route('home')" />
            </div>

            <div data-rise>
                <hr class="rule-brass mb-4 max-w-12" data-rule>
                <p class="t-label text-accent-text">{{ $page['label'] }}</p>
                <blockquote class="auth__epigraph mt-3 text-ink" data-auth-epigraph><p>“{{ $page['epigraph'] }}”</p></blockquote>
            </div>
        </aside>

        <main id="main" class="auth__main">
            <div class="auth__column form-style-underline" data-rise-group>
                <div class="mb-8 hidden justify-center lg:flex">
                    <x-brand.lockup variant="stacked" tone="evergreen" :href="route('home')" />
                </div>

                <header>
                    <h1 id="page-title" class="t-heading">{{ $title }}</h1>
                    @if ($intro)
                        <p class="t-body mt-4 text-ink-muted">{{ $intro }}</p>
                    @endif
                </header>

                <hr class="rule-brass mt-6" data-rule>

                @if (session('status'))
                    @php $status = (string) session('status'); @endphp
                    <x-alert :tone="AuthStatus::isValid($status) ? AuthStatus::tone($status) : StatusTone::Cypress" class="mt-8">{{ AuthStatus::message($status) ?? $status }}</x-alert>
                @endif

                <x-form.error-summary class="mt-8" :targets="$errorTargets" />

                <div class="mt-8">
                    {{ $slot }}
                </div>

                <footer class="mt-10 flex flex-col items-center gap-2 border-t border-rule pt-8">
                    <a href="{{ $page['back']['url'] }}" class="link-hairline" data-auth-back>{{ $page['back']['label'] }}</a>
                    <x-seal class="!mt-4" :size="32" />
                </footer>
            </div>
        </main>
    </div>
</body>
</html>
