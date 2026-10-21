<?php

declare(strict_types=1);

namespace App\View\Components\Site;

use App\Constants\Search;
use App\Services\NavigationService;
use App\Services\TypesetterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\View\Component;

/**
 * Fixed glassy header with the primary navigation, the "More" disclosure for
 * items that do not fit, the search button and dialog, and the full-screen
 * menu for tablet and mobile, which lists the sections as a table of contents.
 */
class Header extends Component
{
    /**
     * How many primary items are shown inline at each desktop breakpoint; the rest go
     * under "More". The bar is capped at max-w-7xl, so from xl the count no longer grows:
     * measured at 1280px, six label-style items beside the lockup and the search button
     * leave a dozen pixels to spare.
     */
    public const int INLINE_AT_LG = 4;

    public const int INLINE_AT_XL = 6;

    /** @var list<array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}> */
    public array $primary;

    /** @var list<array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool, numeral: string}> */
    public array $all;

    public string $searchUrl;

    public string $searchParam = Search::PARAM;

    public string $searchShortcuts;

    public function __construct(NavigationService $navigation, TypesetterService $typesetter, Request $request)
    {
        $current = $request->route()?->getName();

        $this->primary = $navigation->primary($current);
        $this->all = array_map(
            fn (array $item, int $index): array => $item + ['numeral' => $typesetter->roman($index + 1)],
            $navigation->all($current),
            array_keys($navigation->all($current)),
        );
        $this->searchUrl = route(Search::ROUTE);
        $this->searchShortcuts = Search::keyShortcuts();
    }

    /**
     * Tailwind classes that show an inline item only from the breakpoint where it fits.
     */
    public function inlineClasses(int $index): string
    {
        return match (true) {
            $index < self::INLINE_AT_LG => 'hidden lg:block',
            $index < self::INLINE_AT_XL => 'hidden xl:block',
            default => 'hidden',
        };
    }

    /**
     * Tailwind classes that show an item inside "More" only while it is not inline.
     */
    public function overflowClasses(int $index): string
    {
        return match (true) {
            $index < self::INLINE_AT_LG => 'hidden',
            $index < self::INLINE_AT_XL => 'block xl:hidden',
            default => 'block',
        };
    }

    public function hasOverflow(): bool
    {
        return count($this->primary) > self::INLINE_AT_LG;
    }

    public function render(): View
    {
        return view('components.site.header');
    }
}
