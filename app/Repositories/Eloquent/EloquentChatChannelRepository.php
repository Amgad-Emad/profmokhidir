<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ChatChannel;
use App\Repositories\Contracts\ChatChannelRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseEloquentRepository<ChatChannel>
 */
class EloquentChatChannelRepository extends BaseEloquentRepository implements ChatChannelRepositoryInterface
{
    public function __construct(ChatChannel $channel)
    {
        parent::__construct($channel);
    }

    public function findBySlug(string $slug): ?ChatChannel
    {
        return $this->query()->where('slug', $slug)->first();
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return $this->query()->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function allOrdered(): Collection
    {
        return $this->query()->orderBy('name')->get();
    }

    public function setLastMessage(ChatChannel $channel, ?int $messageId): void
    {
        $channel->forceFill(['last_message_id' => $messageId])->save();
    }
}
