<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Locales;
use App\Constants\Pages;
use App\Constants\Sections;
use App\Repositories\Contracts\SectionRepositoryInterface;
use InvalidArgumentException;

/**
 * Builds the navigation shown in the public header, full-screen menu and footer,
 * marking the item that matches the current route. Each section's name comes
 * from Header & footer and its title and summary from its own page in
 * Admin › Pages (PageContentService), the section constants being the default.
 * Sections hidden in Site settings are left out (SectionVisibilityService).
 *
 * @phpstan-type NavItem array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}
 */
class NavigationService
{
    public function __construct(
        private readonly SectionRepositoryInterface $sections,
        private readonly PageContentService $pages,
        private readonly SectionVisibilityService $visibility,
    ) {}

    /**
     * Items for the header navigation.
     *
     * @return list<NavItem>
     */
    public function primary(?string $currentRoute = null): array
    {
        return $this->items($this->sections->withPlacement(Sections::PLACEMENT_PRIMARY), $currentRoute);
    }

    /**
     * Items for the footer and the full-screen menu: every section, in navigation order.
     *
     * @return list<NavItem>
     */
    public function all(?string $currentRoute = null): array
    {
        return $this->items($this->sections->all(), $currentRoute);
    }

    /**
     * Sections that appear in the footer only.
     *
     * @return list<NavItem>
     */
    public function footerOnly(?string $currentRoute = null): array
    {
        return $this->items($this->sections->withPlacement(Sections::PLACEMENT_FOOTER), $currentRoute);
    }

    /**
     * @return NavItem
     */
    public function section(string $key, ?string $currentRoute = null): array
    {
        $definition = $this->sections->find($key);

        if ($definition === null) {
            throw new InvalidArgumentException(sprintf('Unknown section "%s".', $key));
        }

        return $this->item($key, $definition, $currentRoute);
    }

    /**
     * A section is active on its own route and on any nested route (e.g. publications.show).
     */
    /**
     * A section's chapter number: its 1-based place in the site's section order
     * (About is I, CV and Achievements II, and so on), for <x-chapter-opening>.
     */
    public function chapter(string $key): int
    {
        // Hidden sections leave the count, so the visible ones read I, II, III without a gap.
        $keys = array_keys($this->sections->all());
        $visible = $this->visibility->visible($keys);
        $position = array_search($key, in_array($key, $visible, true) ? $visible : $keys, true);

        if ($position === false) {
            throw new InvalidArgumentException(sprintf('Unknown section "%s".', $key));
        }

        return $position + 1;
    }

    public function isActive(string $route, ?string $currentRoute): bool
    {
        if ($currentRoute === null) {
            return false;
        }

        // An Arabic page's route is "ar.x": it is active wherever its English twin is.
        $currentRoute = (string) Locales::baseRouteName($currentRoute);

        return $currentRoute === $route || str_starts_with($currentRoute, $route.'.');
    }

    /**
     * @param  array<string, array{label: string, title: string, path: string, route: string, placement: string, summary: string}>  $definitions
     * @return list<NavItem>
     */
    private function items(array $definitions, ?string $currentRoute): array
    {
        $items = [];

        foreach ($definitions as $key => $definition) {
            if (! $this->visibility->isVisible($key)) {
                continue;
            }

            $items[] = $this->item($key, $definition, $currentRoute);
        }

        return $items;
    }

    /**
     * @param  array{label: string, title: string, path: string, route: string, placement: string, summary: string}  $definition
     * @return NavItem
     */
    private function item(string $key, array $definition, ?string $currentRoute): array
    {
        $page = Pages::forSection($key);

        return [
            'key' => $key,
            'label' => $this->pages->keyed(Pages::NAVIGATION, 'sections')[$key]['label'] ?? __($definition['label']),
            // A heading or intro emptied on a page that shows only what was entered (About) leaves the page,
            // but menus, breadcrumbs and cards still need a name: the section's own.
            'title' => $page === null ? __($definition['title']) : ($this->pages->text($page, 'title') ?: __($definition['title'])),
            'url' => route($definition['route']),
            'route' => $definition['route'],
            'placement' => $definition['placement'],
            'summary' => $page === null ? __($definition['summary']) : ($this->pages->text($page, 'intro') ?: __($definition['summary'])),
            'active' => $this->isActive($definition['route'], $currentRoute),
        ];
    }
}
