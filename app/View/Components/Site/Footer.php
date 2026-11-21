<?php

declare(strict_types=1);

namespace App\View\Components\Site;

use App\Constants\Analytics;
use App\Constants\Locales;
use App\Constants\PageDefinitions\SiteBlocks;
use App\Constants\Pages;
use App\Constants\Taglines;
use App\Services\AnalyticsService;
use App\Services\NavigationService;
use App\Services\PageContentService;
use App\Services\SectionVisibilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

/**
 * Evergreen footer: stacked lockup, the closing line, the footer links,
 * copyright and, while visitor analytics is on, the link to the privacy page.
 * The home page already closes with the closing line above its seal, so there the
 * footer carries the other brand line instead of repeating it. Every line and
 * link comes from Admin › Pages › Header & footer.
 */
class Footer extends Component
{
    public const string HOME_ROUTE = 'home';

    /** The built-in lines (the defaults of the Header & footer blocks). */
    public const string CLOSING_LINE = Taglines::HOME_CLOSING;

    public const string HOME_CLOSING_LINE = Taglines::ABOUT_EPIGRAPH;

    public const string DOMAIN = SiteBlocks::FOOTER_DOMAIN;

    public string $closingLine;

    public string $heading;

    public string $domain;

    public string $copyright;

    public string $privacyLabel;

    /** @var list<array{label: string, url: string, active: bool}> */
    public array $sections = [];

    /** The privacy page, linked only while analytics is on (the page explains it). */
    public ?string $privacyUrl = null;

    public function __construct(NavigationService $navigation, PageContentService $pages, Request $request, AnalyticsService $analytics, SectionVisibilityService $visibility)
    {
        // An Arabic page's route is "ar.x"; the footer treats it as its English twin.
        $route = Locales::baseRouteName($request->route()?->getName());

        if ($analytics->enabled()) {
            $this->privacyUrl = route(Analytics::PRIVACY_ROUTE, absolute: false);
        }

        // Footer links point only at routes chosen from PageLinks; one that is not registered (yet), or into a hidden section, is left out.
        foreach ($pages->items(Pages::NAVIGATION, 'footer_links') as $link) {
            $section = $visibility->sectionForRoute($link['route']);

            if ($section !== null && ! $visibility->isVisible($section)) {
                continue;
            }

            if (Route::has($link['route']) && ($link['route'] !== Analytics::PRIVACY_ROUTE || $this->privacyUrl !== null)) {
                $this->sections[] = ['label' => $link['label'], 'url' => route($link['route']), 'active' => $navigation->isActive($link['route'], $route)];
            }
        }

        $this->closingLine = $pages->text(Pages::NAVIGATION, $route === self::HOME_ROUTE ? 'footer_line_home' : 'footer_line');
        $this->heading = $pages->text(Pages::NAVIGATION, 'footer_heading');
        $this->domain = $pages->text(Pages::NAVIGATION, 'footer_domain');
        $this->copyright = $pages->text(Pages::NAVIGATION, 'copyright', [':year' => date('Y')]);
        $this->privacyLabel = $pages->text(Pages::PRIVACY, 'label');
    }

    public function render(): View
    {
        return view('components.site.footer');
    }
}
