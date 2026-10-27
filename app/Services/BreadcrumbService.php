<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\Breadcrumbs;
use App\Constants\Locales;
use App\Constants\Seo;

/**
 * The breadcrumb trail of a public page, derived from the route name: Home,
 * then the section (when the route lives under one), then the page itself for
 * nested routes. Also builds the matching schema.org BreadcrumbList.
 *
 * @phpstan-type Crumb array{label: string, url: string, current: bool}
 */
class BreadcrumbService
{
    public function __construct(private readonly NavigationService $navigation) {}

    /**
     * The trail for a route, or an empty list on routes that show none.
     *
     * @return list<Crumb>
     */
    public function trail(?string $route, ?string $title = null): array
    {
        // An Arabic page's route is "ar.x": the trail is the same as on its English twin.
        $route = Locales::baseRouteName($route);

        if ($route === null || in_array($route, Breadcrumbs::hidden(), true)) {
            return [];
        }

        $crumbs = [['label' => __(Breadcrumbs::HOME_LABEL), 'url' => route('home'), 'current' => false]];
        $standalone = Breadcrumbs::standalone();

        if (array_key_exists($route, $standalone)) {
            $crumbs[] = ['label' => $title ?: __($standalone[$route]), 'url' => url()->current(), 'current' => true];

            return $crumbs;
        }

        $section = $this->sectionOf($route);

        if ($section === null) {
            if (filled($title)) {
                $crumbs[] = ['label' => $title, 'url' => url()->current(), 'current' => true];
            }

            return $crumbs;
        }

        $nested = $route !== $section['route'];
        $crumbs[] = ['label' => $section['label'], 'url' => $section['url'], 'current' => ! $nested];

        if ($nested) {
            $crumbs[] = ['label' => filled($title) ? $title : $this->fallbackLabel($route), 'url' => url()->current(), 'current' => true];
        }

        return $crumbs;
    }

    /**
     * schema.org BreadcrumbList for the trail, or null when there is no trail.
     *
     * @param  list<Crumb>  $trail
     * @return array<string, mixed>|null
     */
    public function schema(array $trail): ?array
    {
        if ($trail === []) {
            return null;
        }

        return [
            '@context' => Seo::SCHEMA_CONTEXT,
            '@type' => Breadcrumbs::SCHEMA_LIST,
            'itemListElement' => array_map(fn (array $crumb, int $index): array => [
                '@type' => Breadcrumbs::SCHEMA_ITEM,
                'position' => $index + 1,
                'name' => $crumb['label'],
                'item' => $crumb['url'],
            ], $trail, array_keys($trail)),
        ];
    }

    /**
     * The section whose route is the given route or one of its parents (commentary.show => Commentary).
     *
     * @return array{key: string, label: string, url: string, route: string}|null
     */
    private function sectionOf(string $route): ?array
    {
        foreach ($this->navigation->all() as $item) {
            if ($this->navigation->isActive($item['route'], $route)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * "tributes.preview.show" => "Preview" when a page passes no title.
     */
    private function fallbackLabel(string $route): string
    {
        $parts = array_values(array_filter(explode('.', $route), fn (string $part): bool => $part !== 'show' && $part !== 'index'));

        return ucfirst(str_replace(['-', '_'], ' ', end($parts) ?: 'Page'));
    }
}
