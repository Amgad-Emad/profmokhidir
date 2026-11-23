<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AdminNavigation;
use App\Constants\AdminUx;
use App\Constants\ContentTypes;
use App\Constants\Pages;
use App\Constants\Permissions;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Everything the admin frame shows around a screen: the sidebar groups (with
 * the queues' counts and the active item), the breadcrumb trail and the
 * command palette's screens and "New ..." actions. Counts are never cached;
 * a moderator must see a new tribute at once.
 *
 * @phpstan-type NavItem array{key: string, label: string, url: string, icon: string, active: bool, count: int}
 * @phpstan-type Crumb array{label: string, url: ?string}
 */
class AdminShellService
{
    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * @return array{dashboard: NavItem, groups: list<array{key: string, label: string, open: bool, count: int, items: list<NavItem>}>}
     */
    public function sidebar(User $user, ?string $currentRoute): array
    {
        $counts = $this->counts($user);
        $groups = [];

        foreach (AdminNavigation::groups() as $groupKey => $groupLabel) {
            $items = [];

            foreach (AdminNavigation::itemsIn($groupKey) as $key => $item) {
                if (! $user->can($item['permission'])) {
                    continue;
                }

                if ($key === AdminNavigation::PAGES) {
                    array_push($items, ...$this->pageItems());

                    continue;
                }

                $items[] = [
                    'key' => $key,
                    'label' => $item['label'],
                    'url' => route($item['route']),
                    'icon' => AdminUx::icon($key),
                    'active' => $this->isActive($item['route'], $currentRoute),
                    'count' => $counts[$key] ?? 0,
                ];
            }

            if ($items === []) {
                continue;
            }

            $groups[] = [
                'key' => $groupKey,
                'label' => $groupLabel,
                'open' => true,
                'count' => array_sum(array_column($items, 'count')),
                'items' => $items,
            ];
        }

        return [
            'dashboard' => [
                'key' => 'dashboard',
                'label' => __(AdminUx::DASHBOARD_LABEL),
                'url' => route(AdminNavigation::DASHBOARD_ROUTE),
                'icon' => AdminUx::icon('dashboard'),
                'active' => $currentRoute === AdminNavigation::DASHBOARD_ROUTE,
                'count' => 0,
            ],
            'groups' => $groups,
        ];
    }

    /**
     * Items that need attention, by navigation key: pending tributes and
     * applications and open chat reports the user may act on.
     *
     * @return array<string, int>
     */
    public function counts(User $user): array
    {
        $counts = [];

        foreach ($this->dashboard->queues() as $key => $queue) {
            if ($queue['count'] > 0 && $user->can($queue['permission'])) {
                $counts[$key] = $queue['count'];
            }
        }

        return $counts;
    }

    /**
     * Dashboard › group › screen › record, from the route name. The last crumb
     * has no link.
     *
     * @return list<Crumb>
     */
    public function breadcrumbs(?string $currentRoute, ?string $title = null): array
    {
        $crumbs = [['label' => __(AdminUx::DASHBOARD_LABEL), 'url' => route(AdminNavigation::DASHBOARD_ROUTE)]];

        if ($currentRoute === null || $currentRoute === AdminNavigation::DASHBOARD_ROUTE) {
            $crumbs[0]['url'] = null;

            return $crumbs;
        }

        foreach (AdminNavigation::items() as $item) {
            if (! $this->isActive($item['route'], $currentRoute)) {
                continue;
            }

            $groupLabel = AdminNavigation::groups()[$item['group']];

            // A group named like its screen (Pages › Pages) shows once.
            if ($groupLabel !== $item['label']) {
                $crumbs[] = ['label' => $groupLabel, 'url' => null];
            }

            $isScreen = $currentRoute === $item['route'];
            $crumbs[] = ['label' => $item['label'], 'url' => $isScreen ? null : route($item['route'])];

            if (! $isScreen && $title !== null) {
                $crumbs[] = ['label' => $title, 'url' => null];
            }

            return $crumbs;
        }

        if ($title !== null) {
            $crumbs[] = ['label' => $title, 'url' => null];
        }

        return $crumbs;
    }

    /**
     * The command palette's fixed entries: every screen the user may open and
     * a "New ..." action per content type. Content is searched on the server.
     *
     * @return list<array{group: string, label: string, hint: string, url: string, icon: string, keywords: string}>
     */
    public function paletteEntries(User $user): array
    {
        $entries = [];

        if ($user->can(Permissions::MANAGE_CONTENT)) {
            foreach (ContentTypes::all() as $type => $definition) {
                if ($type === ContentTypes::PROFILE) {
                    continue;
                }

                $entries[] = [
                    'group' => __('Create'),
                    'label' => __('New :type', ['type' => mb_strtolower(__($definition['singular']))]),
                    'hint' => AdminNavigation::get($definition['nav'])['label'],
                    'url' => route(ContentTypes::createRoute($type)),
                    'icon' => 'plus',
                    'keywords' => 'add create new '.strtolower($definition['plural']),
                ];
            }

            $entries[] = [
                'group' => __('Create'),
                'label' => __('Upload photos'),
                'hint' => AdminNavigation::get(AdminNavigation::MEDIA_LIBRARY)['label'],
                'url' => route(AdminNavigation::routeFor(AdminNavigation::MEDIA_LIBRARY)).'#upload',
                'icon' => 'upload',
                'keywords' => 'add images pictures album',
            ];
        }

        $entries[] = [
            'group' => __('Go to'),
            'label' => __(AdminUx::DASHBOARD_LABEL),
            'hint' => __('Home of the dashboard'),
            'url' => route(AdminNavigation::DASHBOARD_ROUTE),
            'icon' => AdminUx::icon('dashboard'),
            'keywords' => 'home start attention checklist',
        ];

        foreach (AdminNavigation::items() as $key => $item) {
            if (! $user->can($item['permission'])) {
                continue;
            }

            $entries[] = [
                'group' => __('Go to'),
                'label' => $item['label'],
                'hint' => AdminNavigation::groups()[$item['group']],
                'url' => route($item['route']),
                'icon' => AdminUx::icon($key),
                'keywords' => $key,
            ];
        }

        $entries[] = [
            'group' => __('Go to'),
            'label' => __('View the public site'),
            'hint' => __('Opens the home page'),
            'url' => route('home'),
            'icon' => 'external',
            'keywords' => 'website public home',
        ];

        return $entries;
    }

    /**
     * Every site page as its own link to its editor (the page's saved content),
     * the open one marked active.
     *
     * @return list<array{key: string, label: string, url: string, icon: string, active: bool, count: int}>
     */
    private function pageItems(): array
    {
        $route = request()->route();
        $current = $route?->getName() === Pages::EDIT_ROUTE ? $route->parameter(Pages::ROUTE_PARAMETER) : null;
        $current = $current instanceof Model ? $current->getAttribute('key') : $current;
        $items = [];

        foreach (Pages::definitions() as $key => $definition) {
            $items[] = [
                'key' => AdminNavigation::PAGES.'-'.$key,
                'label' => __($definition['title']),
                'url' => route(Pages::EDIT_ROUTE, [Pages::ROUTE_PARAMETER => $key]),
                'icon' => AdminUx::icon(AdminNavigation::PAGES),
                'active' => $current === $key,
                'count' => 0,
            ];
        }

        return $items;
    }

    private function isActive(string $route, ?string $currentRoute): bool
    {
        return $currentRoute !== null && ($currentRoute === $route || str_starts_with($currentRoute, $route.'.'));
    }
}
