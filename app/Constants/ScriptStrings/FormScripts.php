<?php

declare(strict_types=1);

namespace App\Constants\ScriptStrings;

/**
 * English source strings used by the form scripts (resources/js/forms.js, forms/*, auth.js), in every layout.
 * The layouts print
 * their translations for the current language (App\Constants\ScriptStrings);
 * the scripts read them through t() in resources/js/i18n.js. A string used by
 * a script must be listed here, or it stays in English.
 */
final class FormScripts
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            // Fallbacks only: the components pass these already translated (App\Constants\FormControls).
            ':count word',
            ':count words',
            ':count options',
        ];
    }
}
