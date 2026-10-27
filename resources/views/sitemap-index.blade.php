<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="{{ \App\Constants\Seo::SITEMAP_NAMESPACE }}">
@foreach ($sitemaps as $locale => $loc)
    <sitemap>
        <loc>{{ $loc }}</loc>
    </sitemap>
@endforeach
</sitemapindex>
