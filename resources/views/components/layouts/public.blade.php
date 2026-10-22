{{--
    Public layout: plain Blade + Tailwind + Alpine. Never load Livewire here.
    `title` becomes "Title · Professor Mohamed Osman Khidir" (the home page passes none);
    `description` is cut to 160 characters; `image` is a share card path (App\Constants\SocialCards,
    the shared site card by default); `type` an Open Graph type; `schema` a schema.org array
    (or list of arrays) printed as JSON-LD; `noindex` keeps transactional pages out of search.
    Inner pages get a breadcrumb trail (App\Services\BreadcrumbService) with BreadcrumbList JSON-LD:
    `breadcrumb-title` names the current page when it differs from `title`; `breadcrumbs` passes a
    trail of ['label' => , 'url' => , 'current' => ] outright, or false to show none. `breadcrumb-tone="ivory"`
    sets the trail on evergreen, for pages that open on a dark surface. `hero` marks a page that opens with the
    evergreen hero under the masthead: the bar starts transparent over it and the page reserves no top padding.
--}}
@props(['title' => null, 'description' => null, 'image' => null, 'canonical' => null, 'type' => null, 'schema' => null, 'noindex' => false, 'breadcrumbs' => null, 'breadcrumbTitle' => null, 'breadcrumbTone' => 'evergreen', 'hero' => false])
@inject('breadcrumbService', 'App\Services\BreadcrumbService')
@php
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
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo :title="$title" :description="$description" :image="$image" :canonical="$canonical" :type="$type" :schema="$schema" :noindex="$noindex" />
    <x-brand.favicons />
    <x-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ivory text-evergreen">
    <x-duotone-filter />
    <a href="#main" class="skip-link">Skip to content</a>

    <x-site.header :hero="$hero" />

    <main id="main" tabindex="-1" @class(['site-main', 'site-main--hero' => $hero])>
        @if (count($trail) > 1)
            <div @class(['breadcrumbs-band', 'on-dark bg-evergreen text-champagne' => $breadcrumbTone === 'ivory'])>
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
