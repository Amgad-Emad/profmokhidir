<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ChatRead;

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
}
