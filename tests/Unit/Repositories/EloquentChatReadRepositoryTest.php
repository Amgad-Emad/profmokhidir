<?php

namespace Tests\Unit\Repositories;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use App\Models\User;
use App\Repositories\Contracts\ChatReadRepositoryInterface;
use App\Repositories\Eloquent\EloquentChatReadRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentChatReadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ChatReadRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(ChatReadRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentChatReadRepository::class, $this->repository);
    }

    #[Test]
    public function mark_read_creates_a_cursor_then_advances_but_never_rewinds(): void
    {
        $channel = ChatChannel::factory()->create();
        $user = User::factory()->member()->create();
        $first = ChatMessage::factory()->create(['channel_id' => $channel->id, 'user_id' => $user->id]);
        $second = ChatMessage::factory()->create(['channel_id' => $channel->id, 'user_id' => $user->id]);

        $this->assertNull($this->repository->forUser($channel->id, $user->id));

        $read = $this->repository->markRead($channel->id, $user->id, $first->id);
        $this->assertSame($first->id, $read->last_read_message_id);
        $this->assertTrue($read->is($this->repository->forUser($channel->id, $user->id)));

        $this->repository->markRead($channel->id, $user->id, $second->id);
        $this->assertSame($second->id, $this->repository->forUser($channel->id, $user->id)->last_read_message_id);

        $this->repository->markRead($channel->id, $user->id, $first->id);
        $this->assertSame($second->id, $this->repository->forUser($channel->id, $user->id)->last_read_message_id);

        $this->assertSame(1, $this->repository->all()->count());
    }

    #[Test]
    public function mark_read_is_monotonic_under_out_of_order_updates_and_lists_cursors_per_channel(): void
    {
        $channel = ChatChannel::factory()->create();
        $other = ChatChannel::factory()->create();
        $user = User::factory()->member()->create();
        $messages = ChatMessage::factory()->count(3)->create(['channel_id' => $channel->id, 'user_id' => $user->id]);
        $elsewhere = ChatMessage::factory()->create(['channel_id' => $other->id, 'user_id' => $user->id]);

        $this->repository->markRead($channel->id, $user->id, $messages[2]->id);
        $this->repository->markRead($channel->id, $user->id, $messages[0]->id);
        $this->repository->markRead($other->id, $user->id, $elsewhere->id);

        $this->assertSame($messages[2]->id, $this->repository->forUser($channel->id, $user->id)->last_read_message_id);
        $this->assertSame([$channel->id => $messages[2]->id, $other->id => $elsewhere->id], $this->repository->lastReadIdsFor($user->id));
        $this->assertSame(2, ChatRead::query()->where('user_id', $user->id)->count());
    }
}
