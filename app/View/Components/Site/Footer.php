<?php

declare(strict_types=1);

namespace App\View\Components\Site;

use App\Services\NavigationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * Evergreen footer: stacked lockup, the closing line, section links and copyright.
 */
class Footer extends Component
{
    public const string CLOSING_LINE = 'His lessons continue in every student he taught.';

    public const string DOMAIN = 'profmokhidir.com';

    public string $closingLine = self::CLOSING_LINE;

    public string $domain = self::DOMAIN;

    /** @var list<array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}> */
    public array $sections;

    public function __construct(NavigationService $navigation, Request $request)
    {
        $this->sections = $navigation->all($request->route()?->getName());
    }

    public function render(): View
    {
        return view('components.site.footer');
    }
}
