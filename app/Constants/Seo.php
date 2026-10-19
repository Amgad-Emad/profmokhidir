<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Search and sharing metadata: the site name every <title> ends with, the
 * description length, Open Graph and Twitter card values, schema.org types,
 * and the crawler rules served at /robots.txt and /sitemap.xml.
 */
final class Seo
{
    /** Every public <title> is "Section · Professor Mohamed Osman Khidir"; the home page is the name alone. */
    public const string SITE_NAME = 'Professor Mohamed Osman Khidir';

    public const string TITLE_SEPARATOR = ' · ';

    /** Meta descriptions are cut to this many characters (with an ellipsis). */
    public const int DESCRIPTION_MAX_LENGTH = 160;

    public const string DESCRIPTION_ELLIPSIS = '…';

    public const string LOCALE = 'en_GB';

    public const string OG_TYPE_WEBSITE = 'website';

    public const string OG_TYPE_ARTICLE = 'article';

    public const string OG_TYPE_PROFILE = 'profile';

    public const string TWITTER_CARD = 'summary_large_image';

    /** Robots meta for signed-in and transactional screens. */
    public const string ROBOTS_NOINDEX = 'noindex, nofollow';

    public const string SCHEMA_CONTEXT = 'https://schema.org';

    public const string SCHEMA_PERSON = 'Person';

    public const string SCHEMA_ORGANIZATION = 'Organization';

    public const string SCHEMA_SCHOLARLY_ARTICLE = 'ScholarlyArticle';

    public const string SCHEMA_PROPERTY_VALUE = 'PropertyValue';

    public const string SCHEMA_DOI = 'DOI';

    public const string DOI_BASE_URL = 'https://doi.org/';

    /** Separator between authors in a publication's author line. */
    public const string AUTHOR_SEPARATOR = ';';

    public const string SITEMAP_PATH = '/sitemap.xml';

    public const string SITEMAP_ROUTE = 'sitemap';

    public const string SITEMAP_NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    public const string ROBOTS_PATH = '/robots.txt';

    public const string ROBOTS_ROUTE = 'robots';

    public const string XML_CONTENT_TYPE = 'application/xml; charset=UTF-8';

    public const string TEXT_CONTENT_TYPE = 'text/plain; charset=UTF-8';

    /**
     * Paths crawlers are told to stay out of: the dashboard and the members' chat.
     *
     * @return list<string>
     */
    public static function disallowedPaths(): array
    {
        return ['/admin', '/community/chat'];
    }

    /**
     * @return list<string>
     */
    public static function ogTypes(): array
    {
        return [self::OG_TYPE_WEBSITE, self::OG_TYPE_ARTICLE, self::OG_TYPE_PROFILE];
    }
}
