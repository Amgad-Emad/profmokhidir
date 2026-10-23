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

    /** The one label beside the lockup in the chat header (a link to the community page). */
    public const string HEADER_LABEL = 'Community';

    public const string MEMBER_MENU_LABEL = 'Account menu';

    public const string MENU_ACCOUNT = 'Account';

    public const string MENU_SIGN_OUT = 'Sign out';

    public const string MEMBERS_LINK = 'Members';

    public const string PANEL_TITLE = 'About this room';

    public const string PANEL_CONDUCT = 'Conduct';

    public const string PANEL_PINNED = 'Pinned';

    public const string PANEL_MEMBERS = 'Members';

    public const string PANEL_ONLINE = 'Online now';

    public const string ONLINE_NONE = 'No one else has been here in the last five minutes.';

    public const string ALL_MEMBERS = 'All members';

    public const string PANEL_OPEN = 'About this room';

    public const string PANEL_CLOSE = 'Close the panel';

    /** The side panel's tabs. */
    public const string TAB_ABOUT = 'About';

    public const string TAB_PINNED = 'Pinned';

    public const string TAB_MEMBERS = 'Members';

    public const string PANEL_TABS_LABEL = 'About this room';

    /** The Members tab: search (from 21 members), "online", the member's own entry and the administrators' menu. */
    public const string MEMBER_SEARCH_LABEL = 'Find a member';

    public const string MEMBER_SEARCH_PLACEHOLDER = 'Name or affiliation…';

    public const string MEMBER_SEARCH_NONE = 'No member answers to that.';

    public const string MEMBER_ONLINE = 'online';

    public const string MEMBER_MENU = 'Actions for :name';

    public const string MEMBER_VIEW_PROFILE = 'View profile';

    public const string MEMBER_SUSPEND = 'Suspend';

    public const string MEMBER_SUSPEND_CONFIRM = 'Suspend :name from the community?';

    public const string MEMBER_SUSPENDED = ':name has been suspended.';

    public const string MEMBER_SUSPEND_NOTE = 'Suspended by an administrator from the chat.';

    public const string SEARCH_LABEL = 'Search the conversation';

    public const string SEARCH_PLACEHOLDER = 'Search messages…';

    public const string SEARCH_NONE = 'Nothing in the conversation answers to that.';

    public const string SEARCH_TOO_SHORT = 'Type a word or two to search.';

    public const string COMPOSER_PLACEHOLDER = 'Write to the community…';

    public const string COMPOSER_LABEL = 'Write to the community';

    public const string EDIT_LABEL = 'Edit message';

    public const string REPORT_REASON_LABEL = 'Reason';

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

    public const string STATUS_YOU = 'You';

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

    /** The small "Aa" button that opens the formatting help, and its accessible name. */
    public const string FORMATTING_BUTTON = 'Aa';

    public const string FORMATTING_TOGGLE = 'Formatting help';

    /** The late character count under the composer. */
    public const string COUNT_LEFT = ':count characters left';

    public const string COUNT_OVER = ':count characters over';

    /** Attachments: the paperclip, its conduct note, drag and drop, previews and the cards in a message. */
    public const string ATTACH = 'Attach files';

    public const string ATTACH_NOTE = 'Do not upload material you do not have the right to share.';

    public const string ATTACH_TYPES = 'Images (JPEG, PNG, WebP, HEIC) up to :image MB; PDF, Word, Excel, PowerPoint, CSV and ZIP up to :document MB; at most :max files.';

    public const string DROP_HINT = 'Drop files to attach them';

    public const string ATTACH_DESCRIPTION_LABEL = 'Describe the image (optional)';

    public const string ATTACH_DESCRIPTION_PLACEHOLDER = 'Add a description (optional)';

    /** The alt text of an image sent without a description (ChatAttachmentService::claim). */
    public const string ATTACH_DEFAULT_ALT = 'Image shared by :name';

    public const string ATTACH_REMOVE = 'Remove :name';

    public const string ATTACH_UPLOADING = 'Uploading';

    public const string ATTACH_FAILED = 'Could not upload. Remove it and try again.';

    public const string ATTACH_TOO_MANY = 'Attach at most :max files to one message.';

    public const string ATTACH_TOO_LARGE = ':name is larger than :max MB.';

    public const string ATTACH_UNSUPPORTED = ':name is not a file type the chat accepts.';

    public const string ATTACH_WAIT = 'Wait for the files to finish uploading.';

    public const string ATTACH_DOWNLOAD = 'Download';

    public const string ATTACH_OPEN_IMAGE = 'Open image';

    public const string ATTACHMENTS_LABEL = 'Attachments';

    public const string ATTACH_GALLERY_LABEL = 'Images in the conversation';

    public const string UPLOADS_OFF = 'File uploads are switched off for now.';

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
            'countLeft' => self::COUNT_LEFT,
            'countOver' => self::COUNT_OVER,
            'attachTooMany' => self::ATTACH_TOO_MANY,
            'attachTooLarge' => self::ATTACH_TOO_LARGE,
            'attachUnsupported' => self::ATTACH_UNSUPPORTED,
            'attachFailed' => self::ATTACH_FAILED,
            'attachWait' => self::ATTACH_WAIT,
            'attachDefaultAlt' => self::ATTACH_DEFAULT_ALT,
            'attachRemove' => self::ATTACH_REMOVE,
            'memberMenu' => self::MEMBER_MENU,
            'suspendConfirm' => self::MEMBER_SUSPEND_CONFIRM,
            'memberSearchNone' => self::MEMBER_SEARCH_NONE,
        ];
    }
}
