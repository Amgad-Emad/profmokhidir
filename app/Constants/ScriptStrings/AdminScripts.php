<?php

declare(strict_types=1);

namespace App\Constants\ScriptStrings;

/**
 * English source strings used by the admin (resources/js/admin.js and resources/js/admin/*). The layouts print
 * their translations for the current language (App\Constants\ScriptStrings);
 * the scripts read them through t() in resources/js/i18n.js. A string used by
 * a script must be listed here, or it stays in English.
 */
final class AdminScripts
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            // resources/js/admin/editing.js
            'just now',
            'Saving…',
            'Unsaved changes',
            'Not saved yet',
            'Saved :time',
            // resources/js/admin/palette.js
            'No results',
            '1 result',
            ':count results',
        ];
    }
}
