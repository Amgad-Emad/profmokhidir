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
        $now = now();

        // Create the row if it is missing (ignored when another request got there first)...
        $this->query()->toBase()->insertOrIgnore([
            'channel_id' => $channelId,
            'user_id' => $userId,
            'last_read_message_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ...then move the cursor forward only. GREATEST keeps it monotonic even
        // when two tabs report different positions at the same moment.
        $this->query()
            ->where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->update([
                'last_read_message_id' => $this->model->getConnection()->raw(
                    sprintf('GREATEST(COALESCE(last_read_message_id, 0), %d)', max(0, $messageId))
                ),
                'updated_at' => $now,
            ]);

        /** @var ChatRead $read */
        $read = $this->forUser($channelId, $userId);

        return $read;
    }

    public function lastReadIdsFor(int $userId): array
    {
        return $this->query()->where('user_id', $userId)
            ->pluck('last_read_message_id', 'channel_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
