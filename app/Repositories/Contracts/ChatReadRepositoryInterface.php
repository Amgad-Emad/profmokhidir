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
     * Advance (never rewind) the user's read cursor in a channel.
     */
    public function markRead(int $channelId, int $userId, int $messageId): ChatRead;
}
