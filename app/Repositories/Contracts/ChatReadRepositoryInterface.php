<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ChatRead;
use DateTimeInterface;

/**
 * @extends BaseRepositoryInterface<ChatRead>
 */
interface ChatReadRepositoryInterface extends BaseRepositoryInterface
{
    public function forUser(int $channelId, int $userId): ?ChatRead;

    /**
     * Advance (never rewind) the user's read cursor in a channel, atomically
     * with GREATEST so two tabs cannot move it backwards.
     */
    public function markRead(int $channelId, int $userId, int $messageId): ChatRead;

    /**
     * The user's read cursor per channel: channel id => last read message id.
     *
     * @return array<int, int>
     */
    public function lastReadIdsFor(int $userId): array;

    /**
     * Refresh the timestamp of the user's cursor in a channel (creating an
     * empty cursor if there is none) when it is older than $staleBefore, so
     * the poll can record presence without writing on every request. The read
     * position itself never changes. Returns true when a row was written.
     */
    public function touch(int $channelId, int $userId, DateTimeInterface $staleBefore): bool;

    /**
     * Ids of users whose cursor in any channel was touched at or after $since.
     *
     * @return list<int>
     */
    public function activeUserIdsSince(DateTimeInterface $since): array;
}
