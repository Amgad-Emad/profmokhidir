{{--
    Public layout: plain Blade + Tailwind + Alpine. Never load Livewire here.
    `title` becomes "Title · Professor Mohamed Osman Khidir" (the home page passes none);
    `description` is cut to 160 characters; `image` is a share card path (App\Constants\SocialCards,
    the shared site card by default); `type` an Open Graph type; `schema` a schema.org array
    (or list of arrays) printed as JSON-LD; `noindex` keeps transactional pages out of search.
--}}
@props(['title' => null, 'description' => null, 'image' => null, 'canonical' => null, 'type' => null, 'schema' => null, 'noindex' => false])
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

    <x-site.header />

    <main id="main" tabindex="-1" class="pt-20">
        {{ $slot }}
    </main>

    <x-site.footer />
</body>
</html>
