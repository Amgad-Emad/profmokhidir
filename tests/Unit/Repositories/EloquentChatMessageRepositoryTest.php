<?php

namespace Tests\Unit\Repositories;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use App\Repositories\Contracts\ChatReadRepositoryInterface;
use App\Repositories\Eloquent\EloquentChatMessageRepository;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

        // Deleted messages stay in the stream (as placeholders) but are not counted as unread.
        $this->assertSame([...array_slice($ids, 1), $deleted->id], $this->repository->afterId($this->channel->id, $ids[0])->pluck('id')->all());
        $this->assertTrue($this->repository->afterId($this->channel->id, $ids[0])->last()->trashed());
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

    #[Test]
    public function first_or_create_by_client_uuid_inserts_once_and_then_returns_the_existing_row(): void
    {
        $uuid = (string) Str::uuid();

        [$created, $wasCreated] = $this->repository->firstOrCreateByClientUuid($this->author->id, $uuid, ['channel_id' => $this->channel->id, 'body' => 'first', 'version' => 1]);
        [$again, $wasCreatedAgain] = $this->repository->firstOrCreateByClientUuid($this->author->id, $uuid, ['channel_id' => $this->channel->id, 'body' => 'second', 'version' => 1]);

        $this->assertTrue($wasCreated);
        $this->assertFalse($wasCreatedAgain);
        $this->assertSame($created->id, $again->id);
        $this->assertSame('first', $again->body);
        $this->assertTrue($created->relationLoaded('user'));
        $this->assertSame(1, ChatMessage::query()->count());

        $created->delete();
        [$deleted, $wasCreatedThird] = $this->repository->firstOrCreateByClientUuid($this->author->id, $uuid, ['channel_id' => $this->channel->id, 'body' => 'third', 'version' => 1]);
        $this->assertFalse($wasCreatedThird);
        $this->assertTrue($deleted->trashed());
    }

    #[Test]
    public function changed_since_returns_edited_and_deleted_messages_up_to_the_cursor_only(): void
    {
        Carbon::setTestNow('2026-10-19 10:00:00');
        $untouched = $this->message(['body' => 'same']);
        $edited = $this->message(['body' => 'before']);
        $deleted = $this->message();
        $later = $this->message();
        $since = now()->addSecond();

        Carbon::setTestNow('2026-10-19 10:00:05');
        $this->repository->updateBodyIfVersion($edited, 'after', 1);
        $deleted->delete();
        $this->repository->updateBodyIfVersion($later, 'after the cursor', 1);
        ChatMessage::factory()->edited()->create(); // other channel

        $changed = $this->repository->changedSince($this->channel->id, $since, $deleted->id);

        $this->assertSame([$edited->id, $deleted->id], $changed->pluck('id')->all());
        $this->assertSame('after', $changed->first()->body);
        $this->assertTrue($changed->last()->trashed());
        $this->assertTrue($changed->every(fn (ChatMessage $message): bool => $message->relationLoaded('user')));
        $this->assertCount(0, $this->repository->changedSince($this->channel->id, now()->addMinute(), $later->id));
        $this->assertCount(1, $this->repository->changedSince($this->channel->id, $since, $deleted->id, 1));

        Carbon::setTestNow();
    }

    #[Test]
    public function update_body_if_version_refuses_deleted_messages(): void
    {
        $message = $this->message(['body' => 'x']);
        $message->delete();

        $this->assertFalse($this->repository->updateBodyIfVersion($message, 'y', 1));
        $this->assertSame('x', ChatMessage::withTrashed()->find($message->id)->body);
    }

    #[Test]
    public function unread_counts_and_count_before_cover_every_channel_and_skip_deleted_rows(): void
    {
        $reader = User::factory()->member()->create();
        $other = ChatChannel::factory()->create();
        $first = $this->message();
        $this->message();
        $gone = $this->message();
        $gone->delete();
        ChatMessage::factory()->create(['channel_id' => $other->id, 'user_id' => $this->author->id]);

        $this->assertSame([$this->channel->id => 2, $other->id => 1], $this->repository->unreadCountsFor($reader->id));

        $this->app->make(ChatReadRepositoryInterface::class)->markRead($this->channel->id, $reader->id, $first->id);
        $this->assertSame([$this->channel->id => 1, $other->id => 1], $this->repository->unreadCountsFor($reader->id));

        $this->assertSame(2, $this->repository->countBefore($this->channel->id, $gone->id));
        $this->assertSame(0, $this->repository->countBefore($this->channel->id, $first->id));
        $this->assertTrue($this->repository->findWithTrashed($gone->id)->trashed());
        $this->assertNull($this->repository->findWithTrashed(999));
    }
}
