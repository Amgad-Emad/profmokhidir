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
    /** Longest message body, in characters, enforced on the server. */
    public const int BODY_MAX_LENGTH = 2000;

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

    /** Shown in place of the body of a deleted message. */
    public const string REMOVED_PLACEHOLDER = 'Message removed';

    /** Reconnect delays, in seconds, after a failed poll or send. */
    public const array BACKOFF_SECONDS = [5, 10, 30];

    /** Name of the private broadcast channel per chat channel (Reverb). */
    public const string BROADCAST_CHANNEL_PREFIX = 'community.chat.';

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
     * @return array{transport: string, pollInterval: int, backoff: list<int>, bodyMaxLength: int, pageSize: int, removedPlaceholder: string}
     */
    public static function clientConfig(): array
    {
        return [
            'transport' => self::transport()->value,
            'pollInterval' => self::pollIntervalSeconds(),
            'backoff' => self::BACKOFF_SECONDS,
            'bodyMaxLength' => self::BODY_MAX_LENGTH,
            'pageSize' => self::PAGE_SIZE,
            'removedPlaceholder' => self::REMOVED_PLACEHOLDER,
        ];
    }
}
