<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ChatChannel;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<ChatChannel>
 */
interface ChatChannelRepositoryInterface extends BaseRepositoryInterface
{
    public function findBySlug(string $slug): ?ChatChannel;

    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    /**
     * @return Collection<int, ChatChannel>
     */
    public function allOrdered(): Collection;

    public function setLastMessage(ChatChannel $channel, ?int $messageId): void;
}
