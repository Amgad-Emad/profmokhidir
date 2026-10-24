<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * First-party, cookieless visitor analytics: off by default
 * (SettingKeys::ANALYTICS_ENABLED) and switched on only after the client has
 * approved it. The fixed values of the collector, the visitor hash, the
 * rollups, retention and the admin report live here.
 */
final class Analytics
{
    /** The collector endpoint (POST, CSRF-exempt, rate limited). */
    public const string COLLECT_PATH = '/a/collect';

    public const string COLLECT_ROUTE = 'analytics.collect';

    /** The admin report and its CSV exports. */
    public const string ADMIN_PATH = 'analytics';

    public const string ADMIN_ROUTE = 'admin.analytics';

    public const string EXPORT_ROUTE = 'admin.analytics.export';

    /** The public privacy page (only served while analytics is on). */
    public const string PRIVACY_PATH = '/privacy';

    public const string PRIVACY_ROUTE = 'privacy';

    /** Engaged time counts only while the visitor has scrolled or interacted within this many seconds. */
    public const int IDLE_SECONDS = 30;

    /** Engaged time reported for one page view is capped here (a tab left open overnight). */
    public const int MAX_ENGAGED_SECONDS = 3600;

    /** Scroll depth marks, in per cent of the page. */
    public const array SCROLL_DEPTHS = [25, 50, 75, 100];

    /** A new visit starts after this many minutes without a page view. */
    public const int VISIT_GAP_MINUTES = 30;

    /** Raw events are deleted after this many days; the daily rollups are kept for this many months. */
    public const int RAW_RETENTION_DAYS = 30;

    public const int ROLLUP_RETENTION_MONTHS = 25;

    /** ISO 3166 "user-assigned" code used when the country cannot be told. */
    public const string UNKNOWN_COUNTRY = 'ZZ';

    public const string UNKNOWN_LABEL = 'Unknown';

    /** Label of a page view with no (or an internal) referrer. */
    public const string DIRECT_LABEL = 'Direct or unknown';

    /** Section of the home page, and of any public path outside a section (search, privacy). */
    public const string SECTION_HOME = 'home';

    public const string SECTION_OTHER_PAGES = 'site';

    /** Hash algorithm and salt length (bytes) of the daily visitor hash. */
    public const string HASH_ALGORITHM = 'sha256';

    public const int SALT_BYTES = 32;

    /** The local MaxMind GeoLite2 Country database, relative to storage/app. */
    public const string GEOIP_DATABASE = 'geoip/GeoLite2-Country.mmdb';

    public const string GEOIP_DOWNLOAD_URL = 'https://download.maxmind.com/app/geoip_download';

    public const string GEOIP_EDITION = 'GeoLite2-Country';

    /** Cloudflare's country header (only trusted when ANALYTICS_BEHIND_CLOUDFLARE=true). */
    public const string CLOUDFLARE_COUNTRY_HEADER = 'CF-IPCountry';

    /** Rows in the ranked lists, the top pages table and the dashboard's Audience section. */
    public const int LIST_LIMIT = 8;

    public const int PAGES_LIMIT = 20;

    public const int AUDIENCE_LIMIT = 3;

    /** Ranges longer than this many days are drawn per month instead of per day. */
    public const int DAILY_CHART_MAX_DAYS = 92;

    /** The longest custom range, in days (the rollups' retention). */
    public const int MAX_RANGE_DAYS = 762;

    /** The CSV exports, one per table on the report. */
    public const string EXPORT_VISITORS = 'visitors';

    public const string EXPORT_COUNTRIES = 'countries';

    public const string EXPORT_PAGES = 'pages';

    public const string EXPORT_REFERRERS = 'referrers';

    public const string EXPORT_DEVICES = 'devices';

    public const string EXPORT_BROWSERS = 'browsers';

    /**
     * @return list<string>
     */
    public static function exports(): array
    {
        return [self::EXPORT_VISITORS, self::EXPORT_COUNTRIES, self::EXPORT_PAGES, self::EXPORT_REFERRERS, self::EXPORT_DEVICES, self::EXPORT_BROWSERS];
    }

    /**
     * Paths the collector never records: the dashboard, the members' chat, the
     * account and every sign-in screen (the script is not loaded there either).
     *
     * @return list<string>
     */
    public static function excludedPrefixes(): array
    {
        return [
            '/admin', '/account', '/community/chat', '/login', '/logout', '/register', '/forgot-password',
            '/reset-password', '/two-factor-challenge', '/user', '/email', '/livewire', '/styleguide', '/a/',
        ];
    }

    public static function isExcludedPath(string $path): bool
    {
        foreach (self::excludedPrefixes() as $prefix) {
            if ($path === rtrim($prefix, '/') || str_starts_with($path, rtrim($prefix, '/').'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * User-agent fragments of crawlers, monitors, previews and automated
     * browsers (matched case-insensitively). Lighthouse is among them, so
     * performance runs never count as visitors.
     *
     * @return list<string>
     */
    public static function botPatterns(): array
    {
        return [
            'bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'headless', 'lighthouse', 'pagespeed', 'pingdom',
            'uptime', 'monitor', 'preview', 'facebookexternalhit', 'embedly', 'quora link', 'whatsapp', 'telegram',
            'curl', 'wget', 'python', 'httpclient', 'java/', 'go-http', 'okhttp', 'axios', 'node-fetch', 'guzzle',
            'phantomjs', 'puppeteer', 'playwright', 'selenium', 'webdriver', 'scrapy', 'feedfetcher', 'archive.org',
        ];
    }

    /**
     * Report sections for the pages filter: key => label, home first, then the
     * public sections in navigation order and the remaining site pages.
     *
     * @return array<string, string>
     */
    public static function sections(): array
    {
        $sections = [self::SECTION_HOME => 'Home'];

        foreach (Sections::definitions() as $key => $section) {
            $sections[$key] = $section['label'];
        }

        $sections[self::SECTION_OTHER_PAGES] = 'Other pages';

        return $sections;
    }

    /**
     * The section a public path belongs to.
     */
    public static function sectionFor(string $path): string
    {
        if ($path === '/') {
            return self::SECTION_HOME;
        }

        foreach (Sections::definitions() as $key => $section) {
            if ($path === $section['path'] || str_starts_with($path, $section['path'].'/')) {
                return $key;
            }
        }

        return self::SECTION_OTHER_PAGES;
    }

    public static function isValidSection(string $section): bool
    {
        return array_key_exists($section, self::sections());
    }
}
