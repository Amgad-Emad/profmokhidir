<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ChatRead;
use App\Repositories\Contracts\ChatReadRepositoryInterface;

/**
 * @extends BaseEloquentRepository<ChatRead>
 */
class EloquentChatReadRepository extends BaseEloquentRepository implements ChatReadRepositoryInterface
{
    public function __construct(ChatRead $read)
    {
        parent::__construct($read);
    }

    public function forUser(int $channelId, int $userId): ?ChatRead
    {
        return $this->query()->where('channel_id', $channelId)->where('user_id', $userId)->first();
    }

    public function markRead(int $channelId, int $userId, int $messageId): ChatRead
    {
        $read = $this->query()->firstOrNew(['channel_id' => $channelId, 'user_id' => $userId]);

        if ($read->last_read_message_id === null || $messageId > (int) $read->last_read_message_id) {
            $read->last_read_message_id = $messageId;
        }

        $read->save();

        return $read;
    }
}
