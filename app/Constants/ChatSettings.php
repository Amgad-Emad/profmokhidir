<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Fixed values for the community chat: limits, page sizes, the polling
 * cadence and the reconnect back-off. The transport (polling or Reverb) is
 * the one runtime choice, read from config('community.chat.transport').
 */
final class ChatSettings
{
    /**
     * The one room: a single Scientific Community conversation. It is still a row in
     * chat_channels (every message keeps its channel_id) so the reliability code is unchanged.
     */
    public const string ROOM_SLUG = 'scientific-community';

    public const string ROOM_NAME = 'Scientific Community';

    public const string ROOM_DESCRIPTION = 'One conversation for the approved members of the Scientific Community.';

    /** The side panel's tabs, in order. */
    public const string TAB_ABOUT = 'about';

    public const string TAB_PINNED = 'pinned';

    public const string TAB_MEMBERS = 'members';

    /** @var list<string> */
    public const array TABS = [self::TAB_ABOUT, self::TAB_PINNED, self::TAB_MEMBERS];

    /** The Members tab offers a search once the list is longer than this. */
    public const int MEMBER_SEARCH_THRESHOLD = 20;

    /** The composer grows with its text up to this share of the viewport height, then scrolls inside itself. */
    public const int COMPOSER_MAX_VIEWPORT_PERCENT = 40;

    /** Longest message body, in characters, enforced on the server. */
    public const int BODY_MAX_LENGTH = 2000;

    /** The composer shows the character count once this many characters remain. */
    public const int COUNT_WARNING_REMAINING = 200;

    /** Messages loaded on first render and per "load older" request. */
    public const int PAGE_SIZE = 50;

    /** Most new or changed messages returned by one poll. */
    public const int SYNC_LIMIT = 200;

    /** Default polling cadence for wire:poll. */
    public const int POLL_INTERVAL_SECONDS = 5;

    /** Messages a member may send per minute. */
    public const int SEND_LIMIT_PER_MINUTE = 20;

    /** Longest report reason, in characters. */
    public const int REPORT_REASON_MAX_LENGTH = 500;

    /** Longest channel name. */
    public const int CHANNEL_NAME_MAX_LENGTH = 100;

    /** Consecutive messages by one author within this window share one header. */
    public const int GROUP_WINDOW_SECONDS = 300;

    /** Characters of the original quoted above a reply. */
    public const int REPLY_EXCERPT_LENGTH = 140;

    /** Characters of a pinned message shown in the side panel. */
    public const int PIN_EXCERPT_LENGTH = 160;

    /** Search within a channel: shortest query, longest query, most results. */
    public const int SEARCH_MIN_LENGTH = 2;

    public const int SEARCH_MAX_LENGTH = 100;

    public const int SEARCH_LIMIT = 20;

    /** Query-string parameter of a message permalink (/community/chat?message=123). */
    public const string MESSAGE_PARAM = 'message';

    /** Prefix of the localStorage key that keeps a member's draft. */
    public const string DRAFT_STORAGE_PREFIX = 'chat-draft:';

    /** How long a linked message stays highlighted, in milliseconds. */
    public const int HIGHLIGHT_MS = 2400;

    /** A touch held this long opens a message's actions. */
    public const int LONG_PRESS_MS = 500;

    /** Shown in place of the body of a deleted message. */
    public const string REMOVED_PLACEHOLDER = 'Message removed';

    /** A member counts as online while their read cursor was touched within this window. */
    public const int ONLINE_WINDOW_SECONDS = 300;

    /** The poll refreshes the reader's cursor timestamp at most this often (presence without extra requests). */
    public const int PRESENCE_TOUCH_SECONDS = 60;

    /** The Enter / Shift+Enter hint under the composer is hidden once a member has sent this many messages. */
    public const int HINT_HIDDEN_AFTER_SENDS = 3;

    /** localStorage key counting the messages this browser has sent, for the hint above. */
    public const string SENT_COUNT_STORAGE_KEY = 'chat-sent-count';

    /** Reconnect delays, in seconds, after a failed poll or send. */
    public const array BACKOFF_SECONDS = [5, 10, 30];

    /** Name of the private broadcast channel per chat channel (Reverb). */
    public const string BROADCAST_CHANNEL_PREFIX = 'community.chat.';

    /** Side padding of the room's header, conversation and composer (--chat-gutter in chat.css), below and from 1024px. */
    public const int GUTTER_PX = 24;

    public const int GUTTER_DESKTOP_PX = 40;

    /** Longest line of message text, in characters; the message row and its files still span the column. */
    public const int MESSAGE_MEASURE_CH = 100;

    /** Where the KaTeX stylesheet is served from (copied out of node_modules at build time). */
    public const string KATEX_STYLESHEET = 'vendor/katex/katex.min.css';

    public static function broadcastChannel(int $channelId): string
    {
        return self::BROADCAST_CHANNEL_PREFIX.$channelId;
    }

    public static function transport(): ChatTransport
    {
        return ChatTransport::tryFrom((string) config('community.chat.transport', ChatTransport::default()->value))
            ?? ChatTransport::default();
    }

    public static function pollIntervalSeconds(): int
    {
        return max(1, (int) config('community.chat.poll_interval', self::POLL_INTERVAL_SECONDS));
    }

    /**
     * Everything the chat JavaScript needs to know, in one array.
     *
     * @return array<string, mixed>
     */
    public static function clientConfig(): array
    {
        return [
            'transport' => self::transport()->value,
            'pollInterval' => self::pollIntervalSeconds(),
            'backoff' => self::BACKOFF_SECONDS,
            'bodyMaxLength' => self::BODY_MAX_LENGTH,
            'countWarningRemaining' => self::COUNT_WARNING_REMAINING,
            'hintHiddenAfterSends' => self::HINT_HIDDEN_AFTER_SENDS,
            'sentCountKey' => self::SENT_COUNT_STORAGE_KEY,
            'pageSize' => self::PAGE_SIZE,
            'removedPlaceholder' => __(self::REMOVED_PLACEHOLDER),
            'groupWindowSeconds' => self::GROUP_WINDOW_SECONDS,
            'searchMinLength' => self::SEARCH_MIN_LENGTH,
            'messageParam' => self::MESSAGE_PARAM,
            'draftPrefix' => self::DRAFT_STORAGE_PREFIX,
            'highlightMs' => self::HIGHLIGHT_MS,
            'longPressMs' => self::LONG_PRESS_MS,
            'mathAttribute' => ChatFormat::MATH_ATTRIBUTE,
            'katexStylesheet' => asset(self::KATEX_STYLESHEET),
            'copy' => ChatCopy::client(),
            'tabs' => self::TABS,
            'memberSearchThreshold' => self::MEMBER_SEARCH_THRESHOLD,
            'composerMaxViewportPercent' => self::COMPOSER_MAX_VIEWPORT_PERCENT,
        ];
    }
}
