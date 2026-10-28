<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Admin › Appearance: the routes, the draft preview on the live site, the
 * pages the editor previews beside itself, the export format and the copy
 * of the brand note. Tokens live in ThemeTokens, the suggested templates in
 * ThemePresets and the contrast pairs in ThemeContrast.
 */
final class Appearance
{
    /** The screen, under /admin (AdminNavigation::APPEARANCE). */
    public const string PATH = 'settings/appearance';

    /** Signed link that shows a theme's draft on the live site to the admin who opened it. */
    public const string PREVIEW_ROUTE = 'appearance.preview';

    public const string PREVIEW_PATH = '/appearance/preview/{theme}';

    public const string PREVIEW_END_ROUTE = 'appearance.preview.end';

    public const string PREVIEW_END_PATH = '/appearance/preview/end';

    /** Session key holding the id of the theme being previewed. */
    public const string SESSION_PREVIEW = 'appearance.preview';

    public const int PREVIEW_TTL_MINUTES = 60;

    /** The JSON export: format name and version, checked on import. */
    public const string EXPORT_FORMAT = 'profmokhidir-colour-theme';

    public const int EXPORT_VERSION = 1;

    public const int IMPORT_MAX_KB = 64;

    public const int NAME_MAX = 80;

    /** The live preview beside the editor: key => [label, path]. */
    public const array PREVIEW_PAGES = [
        'home' => ['label' => 'Home page', 'path' => '/'],
        'about' => ['label' => 'About page', 'path' => '/about'],
        'publications' => ['label' => 'Publication list', 'path' => '/publications'],
        'chat' => ['label' => 'Community chat', 'path' => '/community/chat'],
        'form' => ['label' => 'A form', 'path' => '/tributes/new'],
    ];

    public const string DEFAULT_PREVIEW_PAGE = 'home';

    public const string BRAND_NOTE = 'The approved brand guidelines allow only the Evergreen & Ivory palette. Changing colours moves the site away from them; Evergreen & Ivory stays the recommended theme and can be restored at any time.';

    public const string ALTERNATIVES_LABEL = 'Alternative palettes';

    public const string RECOMMENDED_LABEL = 'Brand default · Recommended';
}
