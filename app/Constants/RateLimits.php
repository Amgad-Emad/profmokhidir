<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Names of the named rate limiters registered in AppServiceProvider and the
 * limits they apply.
 */
final class RateLimits
{
    /** Community signups per IP address per hour. */
    public const string COMMUNITY_SIGNUP = 'community-signup';

    public const int COMMUNITY_SIGNUP_PER_HOUR = 5;

    /** Chat messages per member per minute (see ChatSettings::SEND_LIMIT_PER_MINUTE). */
    public const string CHAT_SEND = 'chat-send';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::COMMUNITY_SIGNUP, self::CHAT_SEND];
    }

    public static function chatSendKey(int $userId): string
    {
        return self::CHAT_SEND.':'.$userId;
    }
}
