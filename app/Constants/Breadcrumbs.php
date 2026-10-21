<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * The breadcrumb trail on inner pages: copy, the schema.org types of the
 * structured data, and routes that show no trail.
 */
final class Breadcrumbs
{
    public const string HOME_LABEL = 'Home';

    public const string ARIA_LABEL = 'Breadcrumb';

    /** Printed between crumbs (decorative; the list order carries the meaning). */
    public const string SEPARATOR = '/';

    public const string SCHEMA_LIST = 'BreadcrumbList';

    public const string SCHEMA_ITEM = 'ListItem';

    /**
     * Routes that are a page of their own but belong to no section.
     *
     * @return array<string, string> route name => label
     */
    public static function standalone(): array
    {
        return [
            Search::ROUTE => Search::TITLE,
            AdminNavigation::ACCOUNT_ROUTE => 'Account',
        ];
    }

    /**
     * Routes with no trail: the home page itself.
     *
     * @return list<string>
     */
    public static function hidden(): array
    {
        return ['home'];
    }
}
