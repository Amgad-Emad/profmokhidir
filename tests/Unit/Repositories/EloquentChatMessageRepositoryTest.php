<?php

namespace Tests\Unit\Repositories;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use App\Repositories\Eloquent\EloquentChatMessageRepository;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentChatMessageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ChatMessageRepositoryInterface $repository;

    private ChatChannel $channel;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(ChatMessageRepositoryInterface::class);
        $this->channel = ChatChannel::factory()->create();
        $this->author = User::factory()->member()->create();
    }

    #[Test]
    public function the_interface_is_bound_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentChatMessageRepository::class, $this->repository);
    }

    #[Test]
    public function it_finds_a_message_by_client_uuid_including_soft_deleted_ones(): void
    {
        $uuid = (string) Str::uuid();
        $message = $this->message(['client_uuid' => $uuid]);
        $other = User::factory()->member()->create();

        $this->assertTrue($message->is($this->repository->findByClientUuid($this->author->id, $uuid)));
        $this->assertNull($this->repository->findByClientUuid($other->id, $uuid));

        $message->delete();

        $this->assertTrue($message->is($this->repository->findByClientUuid($this->author->id, $uuid)));
    }

    #[Test]
    public function the_same_user_cannot_send_the_same_client_uuid_twice(): void
    {
        $uuid = (string) Str::uuid();
        $this->message(['client_uuid' => $uuid]);

        $this->expectException(QueryException::class);

        $this->message(['client_uuid' => $uuid]);
    }

    #[Test]
    public function different_users_may_share_a_client_uuid(): void
    {
        $uuid = (string) Str::uuid();
        $this->message(['client_uuid' => $uuid]);

        $second = $this->message(['client_uuid' => $uuid, 'user_id' => User::factory()->member()->create()->id]);

        $this->assertNotNull($second->id);
    }

    #[Test]
    public function latest_in_channel_returns_ascending_ids_with_a_limit_and_before_cursor(): void
    {
        $ids = collect(range(1, 5))->map(fn () => $this->message()->id)->all();
        ChatMessage::factory()->create(); // other channel

        $latest = $this->repository->latestInChannel($this->channel->id, 3);
        $this->assertSame(array_slice($ids, 2), $latest->pluck('id')->all());
        $this->assertTrue($latest->every(fn (ChatMessage $m): bool => $m->relationLoaded('user')));

        $before = $this->repository->latestInChannel($this->channel->id, 2, $ids[2]);
        $this->assertSame([$ids[0], $ids[1]], $before->pluck('id')->all());
    }

    #[Test]
    public function after_id_count_after_and_latest_id_work_per_channel(): void
    {
        $this->assertNull($this->repository->latestIdInChannel($this->channel->id));

        $ids = collect(range(1, 4))->map(fn () => $this->message()->id)->all();
        $deleted = $this->message();
        $deleted->delete();
        ChatMessage::factory()->create(); // other channel

        $this->assertSame(array_slice($ids, 1), $this->repository->afterId($this->channel->id, $ids[0])->pluck('id')->all());
        $this->assertSame([$ids[1], $ids[2]], $this->repository->afterId($this->channel->id, $ids[0], 2)->pluck('id')->all());
        $this->assertSame(3, $this->repository->countAfter($this->channel->id, $ids[0]));
        $this->assertSame(4, $this->repository->countAfter($this->channel->id, 0));
        $this->assertSame(end($ids), $this->repository->latestIdInChannel($this->channel->id));
    }

    #[Test]
    public function update_body_if_version_applies_only_with_the_current_version(): void
    {
        $message = $this->message(['body' => 'original']);

        $this->assertTrue($this->repository->updateBodyIfVersion($message, 'edited', 1));
        $this->assertSame('edited', $message->body);
        $this->assertSame(2, $message->version);
        $this->assertNotNull($message->edited_at);
        $this->assertTrue($message->isEdited());

        $this->assertFalse($this->repository->updateBodyIfVersion($message, 'stale edit', 1));

        $fresh = $message->fresh();
        $this->assertSame('edited', $fresh->body);
        $this->assertSame(2, $fresh->version);
    }

    #[Test]
    public function bodies_with_emoji_and_arabic_text_round_trip(): void
    {
        $body = 'مرحباً بالجميع — glad to be here 🌱🇸🇩';

        $message = $this->message(['body' => $body]);

        $this->assertSame($body, $message->fresh()->body);
        $this->assertSame($body, $this->repository->find($message->id)->body);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function message(array $attributes = []): ChatMessage
    {
        return ChatMessage::factory()->create(array_merge([
            'channel_id' => $this->channel->id,
            'user_id' => $this->author->id,
        ], $attributes));
    }
}
