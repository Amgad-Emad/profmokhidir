<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="{{ \App\Constants\Seo::SITEMAP_NAMESPACE }}">
@foreach ($entries as $entry)
    <url>
        <loc>{{ $entry['loc'] }}</loc>
@if ($entry['lastmod'])
        <lastmod>{{ $entry['lastmod'] }}</lastmod>
@endif
    </url>
@endforeach
</urlset>
