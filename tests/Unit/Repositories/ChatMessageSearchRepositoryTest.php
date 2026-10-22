<?php

namespace Tests\Unit\Repositories;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Fulltext search within a channel, pins and the reply relation. InnoDB's
 * fulltext index only sees committed rows, so this class truncates tables
 * (DatabaseTruncation) instead of wrapping each test in a transaction, and
 * truncates again in tearDown so later RefreshDatabase tests start clean.
 */
class ChatMessageSearchRepositoryTest extends TestCase
{
    use DatabaseTruncation;

    private ChatMessageRepositoryInterface $repository;

    private ChatChannel $channel;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(ChatMessageRepositoryInterface::class);
        $this->channel = ChatChannel::factory()->general()->create();
        $this->author = User::factory()->member()->create();
    }

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    private function message(string $body, ?ChatChannel $channel = null): ChatMessage
    {
        return ChatMessage::factory()->for($channel ?? $this->channel, 'channel')->for($this->author, 'user')->create(['body' => $body]);
    }

    #[Test]
    public function search_matches_every_word_as_a_prefix_within_the_channel_newest_first(): void
    {
        $sorghum = $this->message('The sorghum trials in Gezira were promising.');
        $both = $this->message('Sorghum and millet yields, Gezira 1994.');
        $this->message('Millet only.');
        $this->message('Sorghum elsewhere', ChatChannel::factory()->create());

        $this->assertSame([$both->id, $sorghum->id], $this->repository->search($this->channel->id, 'sorgh gezira')->pluck('id')->all());
        $this->assertSame([$both->id], $this->repository->search($this->channel->id, 'sorghum millet')->pluck('id')->all());
        $this->assertSame([$both->id, $sorghum->id], $this->repository->search($this->channel->id, 'Gezira', 1)->pluck('id')->all() + [1 => $sorghum->id], 'the limit caps results');
        $this->assertCount(1, $this->repository->search($this->channel->id, 'Gezira', 1));
    }

    #[Test]
    public function stopwords_and_short_tokens_fall_back_to_like_and_deleted_messages_are_skipped(): void
    {
        $kept = $this->message('Notes on the AGR 301 problem sets');
        $this->message('Problem sets for AGR 302');
        $deleted = $this->message('AGR 301 again');
        $deleted->delete();

        $this->assertSame([$kept->id], $this->repository->search($this->channel->id, 'problem 301')->pluck('id')->all());
        $this->assertSame([$kept->id], $this->repository->search($this->channel->id, 'on the 301')->pluck('id')->all());
        $this->assertCount(0, $this->repository->search($this->channel->id, '%'));
        $this->assertCount(0, $this->repository->search($this->channel->id, ''));
        $this->assertCount(0, $this->repository->search($this->channel->id, 'nothing-here'));
    }

    #[Test]
    public function search_results_carry_their_author_and_reply_target(): void
    {
        $original = $this->message('Original letter about sorghum');
        $reply = ChatMessage::factory()->for($this->author, 'user')->replyTo($original)->create(['body' => 'Reply about sorghum']);

        $results = $this->repository->search($this->channel->id, 'sorghum');

        $this->assertSame([$reply->id, $original->id], $results->pluck('id')->all());
        $this->assertTrue($results->first()->relationLoaded('user'));
        $this->assertTrue($results->first()->relationLoaded('replyTo'));
        $this->assertTrue($results->first()->replyTo->is($original));
    }

    #[Test]
    public function pinned_lists_live_pins_most_recent_first_and_set_pinned_leaves_updated_at_alone(): void
    {
        $admin = User::factory()->admin()->create();
        $first = $this->message('first');
        $second = $this->message('second');
        $gone = $this->message('gone');
        $updatedAt = $first->updated_at;

        Carbon::setTestNow(now()->addMinute());
        $this->repository->setPinned($first, $admin->id);
        Carbon::setTestNow(now()->addMinute());
        $this->repository->setPinned($second, $admin->id);
        $this->repository->setPinned($gone, $admin->id);
        $gone->delete();

        $pinned = $this->repository->pinned($this->channel->id);

        $this->assertSame([$second->id, $first->id], $pinned->pluck('id')->all());
        $this->assertTrue($pinned->first()->relationLoaded('pinnedBy'));
        $this->assertSame($admin->id, $pinned->first()->pinnedBy->id);
        $this->assertTrue($updatedAt->equalTo($first->fresh()->updated_at), 'A pin is not an edit');

        $this->repository->setPinned($first, null);
        $this->assertNull($first->fresh()->pinned_at);
        $this->assertNull($first->fresh()->pinned_by);
        $this->assertSame([$second->id], $this->repository->pinned($this->channel->id)->pluck('id')->all());

        Carbon::setTestNow();
    }

    #[Test]
    public function listings_eager_load_the_answered_message_and_its_author(): void
    {
        $original = $this->message('Original');
        ChatMessage::factory()->for($this->author, 'user')->replyTo($original)->create(['body' => 'Reply']);

        $latest = $this->repository->latestInChannel($this->channel->id);

        $this->assertTrue($latest->last()->relationLoaded('replyTo'));
        $this->assertTrue($latest->last()->replyTo->relationLoaded('user'));
        $this->assertTrue($this->repository->findWithTrashed($latest->last()->id)->relationLoaded('replyTo'));
    }
}
