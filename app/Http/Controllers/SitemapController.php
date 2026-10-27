<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\Seo;
use App\Services\SitemapService;
use Illuminate\Http\Response;

/**
 * /sitemap.xml: the index of the per-language sitemaps; /sitemaps/{locale}.xml:
 * every published public page in that language with its last change.
 */
class SitemapController extends Controller
{
    public function index(SitemapService $sitemap): Response
    {
        return response(view('sitemap-index', ['sitemaps' => $sitemap->sitemaps()]))
            ->header('Content-Type', Seo::XML_CONTENT_TYPE);
    }

    public function show(SitemapService $sitemap, string $locale): Response
    {
        return response(view('sitemap', ['entries' => $sitemap->entries($locale)]))
            ->header('Content-Type', Seo::XML_CONTENT_TYPE);
    }
}
