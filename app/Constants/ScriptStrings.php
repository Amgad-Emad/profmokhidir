<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\ScriptStrings\AdminScripts;
use App\Constants\ScriptStrings\ChatScripts;
use App\Constants\ScriptStrings\FormScripts;
use App\Constants\ScriptStrings\MediaScripts;
use App\Constants\ScriptStrings\SiteScripts;

/**
 * The interface strings scripts need, per bundle. A layout prints the
 * bundles it loads as window.i18n (English source => translation) in any
 * language but English; resources/js/i18n.js looks strings up there and
 * falls back to the English source.
 */
final class ScriptStrings
{
    public const string SITE = 'site';

    public const string CHAT = 'chat';

    public const string ADMIN = 'admin';

    /** Global the layouts set and resources/js/i18n.js reads. */
    public const string GLOBAL = 'i18n';

    /**
     * @return list<string>
     */
    public static function for(string $bundle): array
    {
        return match ($bundle) {
            self::SITE => [...SiteScripts::all(), ...MediaScripts::all(), ...FormScripts::all()],
            self::CHAT => [...ChatScripts::all(), ...FormScripts::all()],
            self::ADMIN => [...AdminScripts::all(), ...FormScripts::all()],
            default => [],
        };
    }

    /**
     * English source => translation in the current language, for the bundles.
     *
     * @param  list<string>  $bundles
     * @return array<string, string>
     */
    public static function translations(array $bundles): array
    {
        $strings = [];

        foreach ($bundles as $bundle) {
            foreach (self::for($bundle) as $source) {
                $strings[$source] = __($source);
            }
        }

        return $strings;
    }
}
