<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The admin sidebar: groups in display order, each with its items. Every item
 * names the route it links to and the permission required to see it, so the
 * sidebar, the placeholder routes and the "Needs attention" queue links are all
 * derived from this one definition.
 */
final class AdminNavigation
{
    public const string GROUP_CONTENT = 'content';

    public const string GROUP_MEDIA = 'media';

    public const string GROUP_COMMUNITY = 'community';

    public const string GROUP_TRIBUTES = 'tributes';

    public const string GROUP_AUDIENCE = 'audience';

    public const string GROUP_SETTINGS = 'settings';

    /** Route name of the dashboard home. */
    public const string DASHBOARD_ROUTE = 'admin.dashboard';

    /** Route name of the account page (name, email, password, two-factor). */
    public const string ACCOUNT_ROUTE = 'account.show';

    /** Route name prefix of every admin route. */
    public const string ROUTE_PREFIX = 'admin.';

    // Item keys, so queues can be referenced by name.
    public const string PAGES = 'pages';

    public const string PROFILE = 'profile';

    public const string CV = 'cv';

    public const string PUBLICATIONS = 'publications';

    public const string AWARDS = 'awards';

    public const string VIDEOS = 'videos';

    public const string COMMENTARY = 'commentary';

    public const string TEACHING = 'teaching';

    public const string OTHER = 'other';

    public const string HOME_QUOTES = 'home-quotes';

    /** Arabic names of categories, types, courses, topics and tags. */
    public const string TERMS = 'terms';

    public const string ALBUMS = 'albums';

    public const string MEDIA_LIBRARY = 'media-library';

    public const string APPLICATIONS = 'applications';

    public const string MEMBERS = 'members';

    public const string CHAT_REPORTS = 'chat-reports';

    public const string TRIBUTES = 'tributes';

    public const string ANALYTICS = 'analytics';

    public const string USERS = 'users';

    public const string SITE_SETTINGS = 'site-settings';

    public const string APPEARANCE = 'appearance';

    public const string ACCOUNT = 'account';

    /**
     * Group key => heading label (in the current language), in sidebar order.
     *
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return [
            self::GROUP_CONTENT => self::translate('Content'),
            self::GROUP_MEDIA => self::translate('Media'),
            self::GROUP_COMMUNITY => self::translate('Community'),
            self::GROUP_TRIBUTES => self::translate('Tributes'),
            self::GROUP_AUDIENCE => self::translate('Audience'),
            self::GROUP_SETTINGS => self::translate('Settings'),
        ];
    }

    /**
     * Item key => label, group, route name, path (relative to /admin, or absolute
     * when the page lives outside the dashboard) and the permission required.
     *
     * @return array<string, array{label: string, group: string, route: string, path: string, permission: string}>
     */
    public static function items(): array
    {
        return [
            self::PAGES => self::item('Pages', self::GROUP_CONTENT, 'content.pages', 'content/pages', Permissions::MANAGE_CONTENT),
            self::PROFILE => self::item('About & profile', self::GROUP_CONTENT, 'content.profile', 'content/profile', Permissions::MANAGE_CONTENT),
            self::CV => self::item('CV entries', self::GROUP_CONTENT, 'content.cv', 'content/cv', Permissions::MANAGE_CONTENT),
            self::PUBLICATIONS => self::item('Publications', self::GROUP_CONTENT, 'content.publications', 'content/publications', Permissions::MANAGE_CONTENT),
            self::AWARDS => self::item('Awards', self::GROUP_CONTENT, 'content.awards', 'content/awards', Permissions::MANAGE_CONTENT),
            self::COMMENTARY => self::item('Commentary', self::GROUP_CONTENT, 'content.commentary', 'content/commentary', Permissions::MANAGE_CONTENT),
            self::TEACHING => self::item('Teaching materials', self::GROUP_CONTENT, 'content.teaching', 'content/teaching', Permissions::MANAGE_CONTENT),
            self::OTHER => self::item('Other information', self::GROUP_CONTENT, 'content.other', 'content/other', Permissions::MANAGE_CONTENT),
            self::HOME_QUOTES => self::item('Home quotes', self::GROUP_CONTENT, 'content.home-quotes', 'content/home-quotes', Permissions::MANAGE_CONTENT),
            self::TERMS => self::item('Arabic names', self::GROUP_CONTENT, 'content.terms', 'content/terms', Permissions::MANAGE_CONTENT),
            self::VIDEOS => self::item('Videos', self::GROUP_MEDIA, 'media.videos', 'media/videos', Permissions::MANAGE_CONTENT),
            self::ALBUMS => self::item('Pictures & albums', self::GROUP_MEDIA, 'media.albums', 'media/albums', Permissions::MANAGE_CONTENT),
            self::MEDIA_LIBRARY => self::item('Media library', self::GROUP_MEDIA, 'media.library', 'media/library', Permissions::MANAGE_CONTENT),
            self::APPLICATIONS => self::item('Applications', self::GROUP_COMMUNITY, 'community.applications', 'community', Permissions::MANAGE_COMMUNITY),
            self::MEMBERS => self::item('Members', self::GROUP_COMMUNITY, 'community.members', 'community/members', Permissions::MANAGE_COMMUNITY),
            self::CHAT_REPORTS => self::item('Chat reports', self::GROUP_COMMUNITY, 'community.reports', 'community/reports', Permissions::MODERATE_CHAT),
            self::TRIBUTES => self::item('Review tributes', self::GROUP_TRIBUTES, 'tributes.index', 'tributes', Permissions::MODERATE_TRIBUTES),
            self::ANALYTICS => self::item('Analytics', self::GROUP_AUDIENCE, 'analytics', Analytics::ADMIN_PATH, Permissions::VIEW_ANALYTICS),
            self::USERS => self::item('Users & roles', self::GROUP_SETTINGS, 'users.index', 'users', Permissions::MANAGE_USERS),
            self::SITE_SETTINGS => self::item('Site settings', self::GROUP_SETTINGS, 'settings.site', 'settings', Permissions::MANAGE_SETTINGS),
            self::APPEARANCE => self::item('Appearance', self::GROUP_SETTINGS, 'settings.appearance', Appearance::PATH, Permissions::MANAGE_APPEARANCE),
            self::ACCOUNT => self::item('Your account', self::GROUP_SETTINGS, self::ACCOUNT_ROUTE, '/account', Permissions::ACCESS_ADMIN),
        ];
    }

    /**
     * @return array{label: string, group: string, route: string, path: string, permission: string}
     */
    public static function item(string $label, string $group, string $route, string $path, string $permission): array
    {
        return [
            'label' => self::translate($label),
            'group' => $group,
            'route' => str_starts_with($route, self::ROUTE_PREFIX) || $route === self::ACCOUNT_ROUTE ? $route : self::ROUTE_PREFIX.$route,
            'path' => $path,
            'permission' => $permission,
        ];
    }

    /**
     * @return array{label: string, group: string, route: string, path: string, permission: string}
     */
    public static function get(string $key): array
    {
        $items = self::items();

        if (! array_key_exists($key, $items)) {
            throw new \InvalidArgumentException(sprintf('Unknown admin navigation item "%s".', $key));
        }

        return $items[$key];
    }

    public static function routeFor(string $key): string
    {
        return self::get($key)['route'];
    }

    /**
     * Items of one group, in order.
     *
     * @return array<string, array{label: string, group: string, route: string, path: string, permission: string}>
     */
    public static function itemsIn(string $group): array
    {
        return array_filter(self::items(), fn (array $item): bool => $item['group'] === $group);
    }

    /**
     * Items whose screens exist (routes/web.php registers them explicitly).
     *
     * @return list<string>
     */
    public static function built(): array
    {
        return [
            self::PAGES, self::PROFILE, self::CV, self::PUBLICATIONS, self::AWARDS, self::COMMENTARY, self::TEACHING, self::OTHER, self::HOME_QUOTES, self::TERMS,
            self::VIDEOS, self::ALBUMS, self::MEDIA_LIBRARY, self::TRIBUTES,
            self::APPLICATIONS, self::MEMBERS, self::CHAT_REPORTS, self::ANALYTICS, self::USERS, self::SITE_SETTINGS, self::APPEARANCE,
        ];
    }

    public static function isBuilt(string $key): bool
    {
        return in_array($key, self::built(), true);
    }

    /**
     * Items whose page lives under /admin and has no screens of its own yet.
     * Every section is built now, so this is empty; it stays so a future
     * section can be listed before its screens exist.
     *
     * @return array<string, array{label: string, group: string, route: string, path: string, permission: string}>
     */
    public static function placeholders(): array
    {
        return array_filter(
            self::items(),
            fn (array $item, string $key): bool => ! str_starts_with($item['path'], '/') && ! self::isBuilt($key),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public static function isValidGroup(string $group): bool
    {
        return array_key_exists($group, self::groups());
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::items());
    }

    /**
     * A label in the current language (as written when no translator is booted, e.g. plain unit tests).
     */
    private static function translate(string $label): string
    {
        return function_exists('app') && app()->bound('translator') ? __($label) : $label;
    }
}
