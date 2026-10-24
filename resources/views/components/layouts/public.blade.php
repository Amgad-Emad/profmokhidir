{{--
    Public layout: plain Blade + Tailwind + Alpine. Never load Livewire here.
    `title` becomes "Title · Professor Mohamed Osman Khidir" (the home page passes none);
    `description` is cut to 160 characters; `image` is a share card path (App\Constants\SocialCards,
    the shared site card by default); `type` an Open Graph type; `schema` a schema.org array
    (or list of arrays) printed as JSON-LD; `noindex` keeps transactional pages out of search.
    Inner pages get a breadcrumb trail (App\Services\BreadcrumbService) with BreadcrumbList JSON-LD:
    `breadcrumb-title` names the current page when it differs from `title`; `breadcrumbs` passes a
    trail of ['label' => , 'url' => , 'current' => ] outright, or false to show none. `breadcrumb-tone="ivory"`
    sets the trail on the dark band, for pages that open on a dark surface. `hero` marks a page that opens with the
    evergreen hero under the masthead: the bar starts transparent over it and the page reserves no top padding.
    Colours come from the semantic tokens (resources/css/tokens.css); <html> carries the theme attributes
    (App\Services\ThemeService) and <x-theme.head> settles the choice before the first paint.
--}}
@props(['title' => null, 'description' => null, 'image' => null, 'canonical' => null, 'type' => null, 'schema' => null, 'noindex' => false, 'breadcrumbs' => null, 'breadcrumbTitle' => null, 'breadcrumbTone' => 'evergreen', 'hero' => false])
@inject('breadcrumbService', 'App\Services\BreadcrumbService')
@inject('themes', 'App\Services\ThemeService')
@php
    $theme = $themes->current(request());

    $trail = match (true) {
        $breadcrumbs === false => [],
        is_array($breadcrumbs) => $breadcrumbs,
        default => $breadcrumbService->trail(request()->route()?->getName(), $breadcrumbTitle ?? $title),
    };
    $breadcrumbSchema = $breadcrumbService->schema($trail);

    if ($breadcrumbSchema !== null) {
        $schema = match (true) {
            $schema === null || $schema === [] => $breadcrumbSchema,
            array_is_list($schema) => [...$schema, $breadcrumbSchema],
            default => [$schema, $breadcrumbSchema],
        };
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {{ $themes->htmlAttributes($theme) }}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo :title="$title" :description="$description" :image="$image" :canonical="$canonical" :type="$type" :schema="$schema" :noindex="$noindex" />
    <x-theme.head light="evergreen" dark="cypress" />
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-ink">
    <x-duotone-filter />
    <a href="#main" class="skip-link">Skip to content</a>
    <x-site.preview-banner />

    <x-site.header :hero="$hero" />

    @if (session('status') === \App\Constants\AuthStatus::SIGNED_OUT)
        <div class="site-notice" x-data="{ shown: true }" x-show="shown" data-signed-out-notice>
            <x-alert :tone="\App\Constants\AuthStatus::tone(\App\Constants\AuthStatus::SIGNED_OUT)" class="site-notice__alert">
                <span class="flex items-center justify-between gap-4">
                    {{ \App\Constants\AuthStatus::message(\App\Constants\AuthStatus::SIGNED_OUT) }}
                    <x-button variant="icon" type="button" class="site-notice__close" x-on:click="shown = false"><x-icon name="close" class="size-4" label="Dismiss" /></x-button>
                </span>
            </x-alert>
        </div>
    @endif

    <main id="main" tabindex="-1" @class(['site-main', 'site-main--hero' => $hero])>
        @if (count($trail) > 1)
            <div @class(['breadcrumbs-band', 'on-dark bg-surface text-ink' => $breadcrumbTone === 'ivory'])>
                <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">
                    <x-site.breadcrumbs :trail="$trail" :tone="$breadcrumbTone" />
                </div>
            </div>
        @endif
        {{ $slot }}
    </main>

    <x-site.footer />
</body>
</html>
