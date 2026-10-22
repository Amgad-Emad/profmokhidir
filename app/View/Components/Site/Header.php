<?php

declare(strict_types=1);

namespace App\View\Components\Site;

use App\Constants\Masthead;
use App\Constants\Search;
use App\Services\MastheadService;
use App\Services\NavigationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The masthead: a glassy evergreen bar with the lockup, five chapter disclosures
 * (each a mega menu with a featured item), the search button and the tribute
 * pill; a utility line above it on wide screens; and, below lg, the full-screen
 * menu with the chapters as an accordion. Over the home hero the bar starts
 * transparent (`hero`), and it compacts as the reader scrolls down.
 */
class Header extends Component
{
    /** @var list<array{key: string, numeral: string, label: string, strap: string, panelId: string, buttonId: string, items: list<array{key: string, label: string, description: string, url: string, active: bool}>, active: bool, featured: array{kind: string, label: string, title: string, meta: string|null, quote: string|null, url: string, media: Media|null}|null}> */
    public array $chapters;

    public ?string $activeChapter;

    /** @var array{line: string, link: array{label: string, url: string}} */
    public array $utility;

    public string $searchUrl;

    public string $searchParam = Search::PARAM;

    public string $searchShortcuts;

    public string $tributeUrl;

    /** @var list<array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}> */
    public array $sections;

    public function __construct(MastheadService $masthead, NavigationService $navigation, Request $request, public bool $hero = false)
    {
        $current = $request->route()?->getName();
        $user = $request->user();

        $this->sections = $navigation->all($current);
        $this->chapters = $masthead->chapters($current, $user);
        $this->activeChapter = $masthead->activeChapter($current);
        $this->utility = $masthead->utility($user);
        $this->searchUrl = route(Search::ROUTE);
        $this->searchShortcuts = Search::keyShortcuts();
        $this->tributeUrl = route('tributes.create');
    }

    public function menuId(): string
    {
        return Masthead::MENU_ID;
    }

    public function searchId(): string
    {
        return Masthead::SEARCH_ID;
    }

    public function render(): View
    {
        return view('components.site.header');
    }
}
