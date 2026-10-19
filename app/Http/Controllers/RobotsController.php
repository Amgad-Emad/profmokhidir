<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Constants\Seo;
use App\Services\SeoService;
use Illuminate\Http\Response;

/**
 * /robots.txt: crawlers are kept out of the dashboard and the members' chat
 * and pointed at the sitemap.
 */
class RobotsController extends Controller
{
    public function __invoke(SeoService $seo): Response
    {
        return response($seo->robots())->header('Content-Type', Seo::TEXT_CONTENT_TYPE);
    }
}
