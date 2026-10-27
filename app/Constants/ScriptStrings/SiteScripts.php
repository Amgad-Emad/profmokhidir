<?php

declare(strict_types=1);

namespace App\Constants\ScriptStrings;

/**
 * English source strings used by the public site scripts (masthead, search, catalogue).
 * The layouts print
 * their translations for the current language (App\Constants\ScriptStrings);
 * the scripts read them through t() in resources/js/i18n.js. A string used by
 * a script must be listed here, or it stays in English.
 */
final class SiteScripts
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            // resources/js/search.js: the live status while the dialog fetches results.
            'Searching…',
        ];
    }
}
