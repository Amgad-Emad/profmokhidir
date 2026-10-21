<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Sections;
use App\Repositories\Contracts\SectionRepositoryInterface;
use InvalidArgumentException;

/**
 * Builds the navigation shown in the public header, full-screen menu and footer,
 * marking the item that matches the current route.
 *
 * @phpstan-type NavItem array{key: string, label: string, title: string, url: string, route: string, placement: string, summary: string, active: bool}
 */
class NavigationService
{
    public function __construct(private readonly SectionRepositoryInterface $sections) {}

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
        $position = array_search($key, array_keys($this->sections->all()), true);

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
        return [
            'key' => $key,
            'label' => $definition['label'],
            'title' => $definition['title'],
            'url' => route($definition['route']),
            'route' => $definition['route'],
            'placement' => $definition['placement'],
            'summary' => $definition['summary'],
            'active' => $this->isActive($definition['route'], $currentRoute),
        ];
    }
}
