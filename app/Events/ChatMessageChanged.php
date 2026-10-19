<?php

declare(strict_types=1);

namespace App\Events;

use App\Constants\ChatChange;
use App\Constants\ChatSettings;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A message was sent, edited or deleted. Dispatched by ChatService inside its
 * transaction; ShouldDispatchAfterCommit holds it until that transaction
 * commits (Laravel 13's replacement for ShouldBroadcastAfterCommit) and it is
 * broadcast only when the transport is set to broadcasting. Clients never render the payload: they
 * treat the event as a nudge to fetch from their cursor, so nothing is missed
 * or duplicated whether the nudge arrives or not.
 */
class ChatMessageChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $channelId,
        public readonly int $messageId,
        public readonly ChatChange $change,
    ) {}

    public static function for(ChatMessage $message, ChatChange $change): self
    {
        return new self((int) $message->channel_id, (int) $message->getKey(), $change);
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(ChatSettings::broadcastChannel($this->channelId))];
    }

    public function broadcastAs(): string
    {
        return 'message.'.$this->change->value;
    }

    /**
     * @return array{channel_id: int, message_id: int, change: string}
     */
    public function broadcastWith(): array
    {
        return [
            'channel_id' => $this->channelId,
            'message_id' => $this->messageId,
            'change' => $this->change->value,
        ];
    }

    public function broadcastWhen(): bool
    {
        return ChatSettings::transport()->broadcasts();
    }
}
