<?php

declare(strict_types=1);

namespace App\View\Components\Site;

use App\Constants\Taglines;
use App\Services\NavigationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * Evergreen footer: stacked lockup, the closing line, section links and copyright.
 * The home page already closes with the closing line above its seal, so there the
 * footer carries the other brand line instead of repeating it.
 */
class Footer extends Component
{
    public const string CLOSING_LINE = Taglines::HOME_CLOSING;

    public const string HOME_CLOSING_LINE = Taglines::ABOUT_EPIGRAPH;

    public const string HOME_ROUTE = 'home';

    public const string DOMAIN = 'profmokhidir.com';

    public string $closingLine = self::CLOSING_LINE;

    public string $domain = self::DOMAIN;

    /** @var list<array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}> */
    public array $sections;

    public function __construct(NavigationService $navigation, Request $request)
    {
        $route = $request->route()?->getName();

        $this->sections = $navigation->all($route);

        if ($route === self::HOME_ROUTE) {
            $this->closingLine = self::HOME_CLOSING_LINE;
        }
    }

    public function render(): View
    {
        return view('components.site.footer');
    }
}
