<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Fixed copy for the correspondence room (the community chat): panel titles,
 * the composer's placeholder and hint, the quiet state labels and the empty
 * and edge-state notices. Edited here, never in the views or the script.
 */
final class ChatCopy
{
    public const string TITLE = 'Correspondence';

    public const string CHANNELS_HEADING = 'Channels';

    public const string PANEL_TITLE = 'About this channel';

    public const string PANEL_CONDUCT = 'Conduct';

    public const string PANEL_PINNED = 'Pinned';

    public const string PANEL_MEMBERS = 'Members';

    public const string PANEL_OPEN = 'About this channel';

    public const string PANEL_CLOSE = 'Close the panel';

    public const string BACK_TO_CHANNELS = 'Channels';

    public const string SEARCH_LABEL = 'Search this channel';

    public const string SEARCH_PLACEHOLDER = 'Search messages…';

    public const string SEARCH_NONE = 'Nothing in this channel answers to that.';

    public const string SEARCH_TOO_SHORT = 'Type a word or two to search.';

    public const string COMPOSER_PLACEHOLDER = 'Write to the community…';

    public const string COMPOSER_LABEL = 'Write to the community';

    public const string COMPOSER_HINT = 'Enter to send · Shift+Enter for a new line';

    public const string SEND = 'Send';

    public const string SENDING = 'Sending';

    public const string NOT_SENT = 'Not sent';

    public const string RETRY = 'Retry';

    public const string RECONNECTING = 'Reconnecting…';

    public const string OFFLINE_BANNER = 'You are offline. Messages you write will be sent when the connection returns.';

    public const string EXPIRED = 'Your session has expired.';

    public const string SIGN_IN_AGAIN = 'Sign in again';

    public const string NEW_DIVIDER = 'New';

    public const string EDITED = 'edited';

    public const string PINNED = 'pinned';

    public const string REPLYING_TO = 'Replying to';

    public const string REPLY_REMOVED = 'Original message removed';

    public const string CANCEL_REPLY = 'Cancel reply';

    public const string ACTION_REPLY = 'Reply';

    public const string ACTION_COPY_LINK = 'Copy link';

    public const string ACTION_EDIT = 'Edit';

    public const string ACTION_DELETE = 'Delete';

    public const string ACTION_REPORT = 'Report';

    public const string ACTION_PIN = 'Pin';

    public const string ACTION_UNPIN = 'Unpin';

    public const string LINK_COPIED = 'Link copied';

    public const string NO_PINS = 'Nothing has been pinned yet.';

    public const string EMPTY_EPIGRAPH = 'Every correspondence begins with a first letter.';

    public const string EMPTY_ATTRIBUTION = 'The archivist';

    public const string MEMBERS_ONLINE_NOTE = 'Messages are visible to approved members and administrators only.';

    public const string STATUS_MEMBER = 'Member';

    public const string STATUS_ADMIN = 'Administrator';

    public const string STATUS_YOU = 'you';

    public const string MESSAGE_FOCUSED = 'Linked message';

    public const string JUMP_TO_LATEST = 'Jump to the latest';

    public const string LOAD_OLDER = 'Earlier letters';

    public const string NEW_MESSAGES_ONE = '1 new message';

    public const string NEW_MESSAGES_MANY = ':count new messages';

    public const string REPORT_TITLE = 'Report this message';

    public const string REPORT_INTRO = 'Tell the administrators briefly what is wrong. They will see the message, your name and your reason.';

    public const string REPORT_THANKS = 'Thank you. An administrator will look at this message.';

    public const string DELETE_CONFIRM = 'Remove this message?';

    public const string FORMATTING_HEADING = 'Formatting';

    /**
     * Everything the script needs as copy, keyed for the client config.
     *
     * @return array<string, string>
     */
    public static function client(): array
    {
        return [
            'sending' => self::SENDING,
            'notSent' => self::NOT_SENT,
            'retry' => self::RETRY,
            'reconnecting' => self::RECONNECTING,
            'offline' => self::OFFLINE_BANNER,
            'linkCopied' => self::LINK_COPIED,
            'replyRemoved' => self::REPLY_REMOVED,
            'newOne' => self::NEW_MESSAGES_ONE,
            'newMany' => self::NEW_MESSAGES_MANY,
            'searchNone' => self::SEARCH_NONE,
            'searchTooShort' => self::SEARCH_TOO_SHORT,
            'reportThanks' => self::REPORT_THANKS,
        ];
    }
}
