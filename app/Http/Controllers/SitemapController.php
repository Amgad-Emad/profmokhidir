<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\Seo;
use App\Services\SitemapService;
use Illuminate\Http\Response;

/**
 * /sitemap.xml: every published public page with its last change.
 */
class SitemapController extends Controller
{
    public function __invoke(SitemapService $sitemap): Response
    {
        return response(view('sitemap', ['entries' => $sitemap->entries()]))
            ->header('Content-Type', Seo::XML_CONTENT_TYPE);
    }
}
