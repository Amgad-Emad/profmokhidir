<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Cache keys for public page queries. Services remember under these keys and
 * forget them whenever the related content is saved.
 */
final class CacheKeys
{
    public const int TTL_SECONDS = 3600;

    public const string PROFILE = 'public.profile';

    /** The active colour theme, resolved (both modes and the <head> CSS); forgotten whenever a theme is saved. */
    public const string ACTIVE_THEME = 'appearance.active_theme';

    public const string CV_ENTRIES = 'public.cv_entries';

    public const string PUBLICATIONS = 'public.publications';

    public const string PUBLICATIONS_FILTERS = 'public.publications.filters';

    public const string PUBLICATIONS_LATEST = 'public.publications.latest';

    public const string PUBLICATIONS_FEATURED = 'public.publications.featured';

    public const string AWARDS = 'public.awards';

    public const string VIDEOS = 'public.videos';

    public const string ALBUMS = 'public.albums';

    public const string ALBUMS_NEWEST = 'public.albums.newest';

    public const string TEACHING_MATERIALS = 'public.teaching_materials';

    public const string COMMENTARIES = 'public.commentaries';

    public const string OTHER_ITEMS = 'public.other_items';

    /** Prefix of the cached names of grouping terms in one language (+ locale). */
    public const string TERM_TRANSLATIONS = 'public.term_translations.';

    public const string TRIBUTES = 'public.tributes';

    public const string TRIBUTES_FEATURED = 'public.tributes.featured';

    public const string HOME_QUOTES = 'public.home_quotes';

    /** The portrait on the sign-in screens (the published profile's, or a featured archive photo). */
    public const string AUTH_PORTRAIT = 'public.auth.portrait';

    /** Prefix of each editable page's published blocks (Admin › Pages): "public.pages.<page key>". */
    public const string PAGE_PREFIX = 'public.pages.';

    /** Site settings (contact details, notification address, mail sender). */
    public const string SETTINGS = 'settings.all';

    public static function page(string $page): string
    {
        return self::PAGE_PREFIX.$page;
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PROFILE, self::CV_ENTRIES, self::PUBLICATIONS, self::AWARDS, self::VIDEOS,
            self::ALBUMS, self::ALBUMS_NEWEST, self::TEACHING_MATERIALS, self::COMMENTARIES, self::OTHER_ITEMS, self::TRIBUTES, self::TRIBUTES_FEATURED,
            self::HOME_QUOTES, self::AUTH_PORTRAIT, self::SETTINGS,
            ...array_map(self::page(...), Pages::all()),
        ];
    }
}
