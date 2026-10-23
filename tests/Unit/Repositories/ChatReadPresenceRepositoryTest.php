<?php

namespace Tests\Unit\Repositories;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use App\Models\User;
use App\Repositories\Contracts\ChatReadRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Presence from the read cursors: touch() refreshes a cursor's timestamp at
 * most once per window without moving the read position, and
 * activeUserIdsSince() lists whoever touched one recently.
 */
class ChatReadPresenceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ChatReadRepositoryInterface $reads;

    private ChatChannel $channel;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reads = $this->app->make(ChatReadRepositoryInterface::class);
        $this->channel = ChatChannel::factory()->general()->create();
        $this->member = User::factory()->member()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function touch_creates_an_empty_cursor_when_there_is_none(): void
    {
        $written = $this->reads->touch($this->channel->id, $this->member->id, now()->subMinute());

        $this->assertTrue($written);
        $read = ChatRead::query()->where('channel_id', $this->channel->id)->where('user_id', $this->member->id)->sole();
        $this->assertNull($read->last_read_message_id);
    }

    #[Test]
    public function touch_writes_only_when_the_cursor_is_older_than_the_window_and_never_moves_it(): void
    {
        $message = ChatMessage::factory()->for($this->channel, 'channel')->create();
        Carbon::setTestNow('2026-10-23 10:00:00');
        $this->reads->markRead($this->channel->id, $this->member->id, $message->id);

        Carbon::setTestNow('2026-10-23 10:00:30');
        $this->assertFalse($this->reads->touch($this->channel->id, $this->member->id, now()->subMinute()));

        Carbon::setTestNow('2026-10-23 10:02:00');
        $this->assertTrue($this->reads->touch($this->channel->id, $this->member->id, now()->subMinute()));

        $read = $this->reads->forUser($this->channel->id, $this->member->id);
        $this->assertSame($message->id, (int) $read->last_read_message_id);
        $this->assertSame('2026-10-23 10:02:00', $read->updated_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function active_users_are_those_with_a_cursor_touched_since_the_given_moment_in_any_channel(): void
    {
        $other = User::factory()->member()->create();
        $away = User::factory()->member()->create();
        $second = ChatChannel::factory()->create();

        Carbon::setTestNow('2026-10-23 09:00:00');
        $this->reads->touch($this->channel->id, $away->id, now());

        Carbon::setTestNow('2026-10-23 10:00:00');
        $this->reads->touch($this->channel->id, $this->member->id, now());
        $this->reads->touch($second->id, $other->id, now());
        $this->reads->touch($this->channel->id, $other->id, now());

        $active = $this->reads->activeUserIdsSince(CarbonImmutable::parse('2026-10-23 09:55:00'));

        $this->assertSame([$this->member->id, $other->id], $active);
    }
}
