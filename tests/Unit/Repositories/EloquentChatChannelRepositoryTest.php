<?php

namespace Tests\Unit\Repositories;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Repositories\Contracts\ChatChannelRepositoryInterface;
use App\Repositories\Eloquent\EloquentChatChannelRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentChatChannelRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ChatChannelRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(ChatChannelRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentChatChannelRepository::class, $this->repository);
    }

    #[Test]
    public function it_finds_by_slug_and_checks_slug_existence(): void
    {
        $general = ChatChannel::factory()->general()->create();

        $this->assertTrue($general->is($this->repository->findBySlug('general')));
        $this->assertNull($this->repository->findBySlug('missing'));
        $this->assertTrue($this->repository->slugExists('general'));
        $this->assertFalse($this->repository->slugExists('general', $general->id));
        $this->assertFalse($this->repository->slugExists('missing'));
    }

    #[Test]
    public function all_ordered_sorts_channels_by_name(): void
    {
        ChatChannel::factory()->create(['name' => 'Zeta', 'slug' => 'zeta']);
        ChatChannel::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
        ChatChannel::factory()->create(['name' => 'Mid', 'slug' => 'mid']);

        $this->assertSame(['Alpha', 'Mid', 'Zeta'], $this->repository->allOrdered()->pluck('name')->all());
    }

    #[Test]
    public function set_last_message_updates_and_clears_the_pointer(): void
    {
        $channel = ChatChannel::factory()->create();
        $message = ChatMessage::factory()->create(['channel_id' => $channel->id]);

        $this->repository->setLastMessage($channel, $message->id);
        $this->assertSame($message->id, $channel->fresh()->last_message_id);
        $this->assertTrue($message->is($channel->fresh()->lastMessage));

        $this->repository->setLastMessage($channel, null);
        $this->assertNull($channel->fresh()->last_message_id);
    }

    #[Test]
    public function deleting_the_last_message_nulls_the_pointer_via_the_foreign_key(): void
    {
        $channel = ChatChannel::factory()->create();
        $message = ChatMessage::factory()->create(['channel_id' => $channel->id]);
        $this->repository->setLastMessage($channel, $message->id);

        $message->forceDelete();

        $this->assertNull($channel->fresh()->last_message_id);
    }
}
