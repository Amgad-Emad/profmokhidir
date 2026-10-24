<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Copy and icons of the confirmation dialogs (<x-admin.confirm-dialog>, the chat's
 * delete and report dialogs). Irreversible actions ask first, with the danger icon,
 * a title naming the thing, what will happen, Cancel focused and the filled danger
 * button; reversible ones (move to trash) act at once and offer Undo in the toast.
 */
final class ConfirmDialog
{
    public const string CANCEL = 'Cancel';

    /** The icon on the confirm button of a delete (the same trash icon as the action that opened it). */
    public const string DELETE_ICON = 'trash';

    /** Closes the body of every irreversible dialog. */
    public const string IRREVERSIBLE = 'This cannot be undone.';

    /** The permanent delete from a content type's trash. */
    public const string PURGE_LABEL = 'Delete permanently';

    public const string PURGE_TITLE = 'Delete this :type permanently?';

    public const string PURGE_BODY = '“:title” will be removed from the trash for good, together with its files and images.';

    /**
     * The title of the permanent delete dialog for one content type ("publication").
     */
    public static function purgeTitle(string $singular): string
    {
        return strtr(self::PURGE_TITLE, [':type' => mb_strtolower($singular)]);
    }

    /**
     * What a permanent delete removes, ending with the irreversible note.
     */
    public static function purgeBody(string $title): string
    {
        return strtr(self::PURGE_BODY, [':title' => $title]).' '.self::IRREVERSIBLE;
    }
}
