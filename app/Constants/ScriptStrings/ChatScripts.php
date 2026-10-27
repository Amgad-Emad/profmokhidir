<?php

declare(strict_types=1);

namespace App\Constants\ScriptStrings;

/**
 * English source strings used by the community chat (resources/js/chat.js). The layouts print
 * their translations for the current language (App\Constants\ScriptStrings);
 * the scripts read them through t() in resources/js/i18n.js. A string used by
 * a script must be listed here, or it stays in English.
 */
final class ChatScripts
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            'Today',
            'Yesterday',
            'Copy this link',
            '1 message',
            ':count messages',
            'Messages can be at most :max characters.',
            'A message cannot be empty.',
            'The change could not be saved.',
            'The change could not be saved. Check your connection and try again.',
            'Please say briefly why you are reporting this message.',
            'The report could not be sent.',
            'The report could not be sent. Check your connection and try again.',
        ];
    }
}
