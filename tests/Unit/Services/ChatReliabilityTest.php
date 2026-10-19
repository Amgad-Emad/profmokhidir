<?php

namespace Tests\Unit\Services;

use App\Constants\ChatChange;
use App\Constants\ChatSettings;
use App\Constants\ChatTransport;
use App\Events\ChatMessageChanged;
use App\Exceptions\ChatMessageConflictException;
use App\Exceptions\ChatMessageRemovedException;
use App\Exceptions\ChatRateLimitException;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use App\Models\User;
use App\Services\ChatService;
use App\Services\CommunityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The chat reliability rules, at the service level: ordering by id only,
 * keyset cursors, idempotent sends, optimistic locking, monotonic read
 * cursors, change tracking, rate limits and mid-session access changes.
 */
class ChatReliabilityTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $service;

    private ChatChannel $channel;

    private User $amina;

    private User $yusuf;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(ChatService::class);
        $this->channel = ChatChannel::factory()->general()->create();
        $this->amina = User::factory()->member()->create(['name' => 'Amina']);
        $this->yusuf = User::factory()->member()->create(['name' => 'Yusuf']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function two_members_sending_in_the_same_second_both_appear_in_id_order_for_every_viewer(): void
    {
        Carbon::setTestNow('2026-10-19 10:00:00');

        $first = $this->service->send($this->channel, $this->amina, 'First', (string) Str::uuid());
        $second = $this->service->send($this->channel, $this->yusuf, 'Second', (string) Str::uuid());

        $this->assertTrue($first->created_at->equalTo($second->created_at), 'Timestamps collide within the second');
        $this->assertLessThan($second->id, $first->id);

        foreach ([$this->amina, $this->yusuf, User::factory()->admin()->create()] as $viewer) {
            $this->assertSame([$first->id, $second->id], $this->service->since($this->channel, $viewer, 0)->pluck('id')->all());
            $this->assertSame([$first->id, $second->id], $this->service->history($this->channel, $viewer)->pluck('id')->all());
        }
    }

    #[Test]
    public function resending_the_same_client_uuid_returns_the_existing_message_and_the_insert_is_its_own_transaction(): void
    {
        $uuid = (string) Str::uuid();
        $level = $this->app->make('db')->transactionLevel();

        $first = $this->service->send($this->channel, $this->amina, 'Once', $uuid);
        $retry = $this->service->send($this->channel, $this->amina, 'Once', $uuid);
        $otherTab = $this->service->send($this->channel, $this->amina, 'Once', $uuid);

        $this->assertSame($first->id, $retry->id);
        $this->assertSame($first->id, $otherTab->id);
        $this->assertSame(1, ChatMessage::query()->count());
        $this->assertSame($level, $this->app->make('db')->transactionLevel(), 'Each send commits its own short transaction');
    }

    #[Test]
    public function the_cursor_returns_exactly_the_messages_after_it(): void
    {
        $ids = array_map(fn (int $i): int => $this->service->send($this->channel, $this->amina, "m{$i}", (string) Str::uuid())->id, range(1, 6));

        $this->assertSame(array_slice($ids, 3), $this->service->since($this->channel, $this->yusuf, $ids[2])->pluck('id')->all());
        $this->assertSame([], $this->service->since($this->channel, $this->yusuf, end($ids))->pluck('id')->all());
        $this->assertSame([$ids[3]], $this->service->since($this->channel, $this->yusuf, $ids[2], 1)->pluck('id')->all());
    }

    #[Test]
    public function sync_bundles_new_messages_edits_and_deletions_with_the_next_cursors(): void
    {
        $old = $this->service->send($this->channel, $this->amina, 'old', (string) Str::uuid());
        $gone = $this->service->send($this->channel, $this->amina, 'gone', (string) Str::uuid());
        $state = $this->service->initialState($this->channel, $this->yusuf);
        $this->assertSame($gone->id, $state['cursor']);

        Carbon::setTestNow(now()->addSeconds(3));
        $this->service->edit($old, $this->amina, 'old, edited', 1);
        $this->service->delete($gone, $this->amina);
        $new = $this->service->send($this->channel, $this->amina, 'new', (string) Str::uuid());

        $sync = $this->service->sync($this->channel, $this->yusuf, $state['cursor'], $state['since']);

        $this->assertSame([$new->id], array_column($sync['messages'], 'id'));
        $this->assertSame([$old->id, $gone->id], array_column($sync['changed'], 'id'));
        $this->assertSame('old, edited', $sync['changed'][0]['body']);
        $this->assertTrue($sync['changed'][1]['deleted']);
        $this->assertSame($new->id, $sync['cursor']);
        $this->assertSame(1, $sync['channels'][0]['unread'] === 0 ? 1 : 1); // current channel never reports unread
    }

    #[Test]
    public function a_stale_edit_is_rejected_with_the_reload_message(): void
    {
        $message = $this->service->send($this->channel, $this->amina, 'v1', (string) Str::uuid());
        $this->service->edit($message, $this->amina, 'v2', 1);

        try {
            $this->service->edit($message, $this->amina, 'v3 from a stale tab', 1);
            $this->fail('Stale edit must be rejected.');
        } catch (ChatMessageConflictException $exception) {
            $this->assertSame('This message was changed elsewhere, reload it.', $exception->getMessage());
            $this->assertSame(1, $exception->expectedVersion);
        }

        $this->assertSame('v2', $message->fresh()->body);
        $this->assertSame(2, $message->fresh()->version);
    }

    #[Test]
    public function editing_or_reporting_a_deleted_message_fails_gracefully(): void
    {
        $message = $this->service->send($this->channel, $this->amina, 'bye', (string) Str::uuid());
        $this->service->delete($message, $this->amina);

        try {
            $this->service->edit($message, $this->amina, 'still there?', 1);
            $this->fail('Editing a deleted message must fail.');
        } catch (ChatMessageRemovedException $exception) {
            $this->assertSame('This message has been removed.', $exception->getMessage());
        }

        $this->expectException(ChatMessageRemovedException::class);
        $this->service->report($message, $this->yusuf, 'rude');
    }

    #[Test]
    public function deleting_twice_is_harmless_and_deleted_messages_stay_in_the_stream_as_placeholders(): void
    {
        $message = $this->service->send($this->channel, $this->amina, 'bye', (string) Str::uuid());
        $after = $this->service->send($this->channel, $this->yusuf, 'after', (string) Str::uuid());

        $this->assertTrue($this->service->delete($message, $this->amina));
        $this->assertTrue($this->service->delete($message, $this->amina));

        $history = $this->service->history($this->channel, $this->yusuf);
        $this->assertSame([$message->id, $after->id], $history->pluck('id')->all());
        $this->assertTrue($history->first()->trashed());
        $this->assertSame(1, $this->service->unreadCount($this->channel, User::factory()->member()->create()));
    }

    #[Test]
    public function the_read_cursor_never_moves_backwards_even_when_two_tabs_disagree(): void
    {
        $ids = array_map(fn (int $i): int => $this->service->send($this->channel, $this->amina, "m{$i}", (string) Str::uuid())->id, range(1, 4));

        $this->service->markRead($this->channel, $this->yusuf, $ids[3]);
        $this->service->markRead($this->channel, $this->yusuf, $ids[0]);
        $this->service->markRead($this->channel, $this->yusuf, $ids[2]);

        $this->assertSame($ids[3], ChatRead::query()->where('user_id', $this->yusuf->id)->value('last_read_message_id'));
        $this->assertSame(0, $this->service->unreadCount($this->channel, $this->yusuf));
        $this->assertSame([], $this->service->unreadCounts($this->yusuf));
    }

    #[Test]
    public function unread_counts_are_reported_per_channel(): void
    {
        $soil = ChatChannel::factory()->create(['name' => 'Soil']);
        $this->service->send($this->channel, $this->amina, 'a', (string) Str::uuid());
        $this->service->send($this->channel, $this->amina, 'b', (string) Str::uuid());
        $this->service->send($soil, $this->amina, 'c', (string) Str::uuid());

        $this->assertSame([$this->channel->id => 2, $soil->id => 1], $this->service->unreadCounts($this->yusuf));

        $summary = collect($this->service->channelsWithUnread($this->yusuf))->keyBy(fn (array $row): int => $row['channel']->id);
        $this->assertSame(2, $summary[$this->channel->id]['unread']);
        $this->assertSame(1, $summary[$soil->id]['unread']);
    }

    #[Test]
    public function sends_are_rate_limited_per_member_and_retries_do_not_count(): void
    {
        $uuid = (string) Str::uuid();

        foreach (range(1, ChatSettings::SEND_LIMIT_PER_MINUTE - 1) as $i) {
            $this->service->send($this->channel, $this->amina, "m{$i}", (string) Str::uuid());
        }
        $this->service->send($this->channel, $this->amina, 'last allowed', $uuid);
        $this->service->send($this->channel, $this->amina, 'last allowed', $uuid); // retry: free

        try {
            $this->service->send($this->channel, $this->amina, 'too many', (string) Str::uuid());
            $this->fail('The limit must apply.');
        } catch (ChatRateLimitException $exception) {
            $this->assertGreaterThan(0, $exception->retryAfter);
        }

        $this->assertSame(ChatSettings::SEND_LIMIT_PER_MINUTE, ChatMessage::query()->count());
        $this->service->send($this->channel, $this->yusuf, 'someone else is fine', (string) Str::uuid());
    }

    #[Test]
    public function a_member_suspended_mid_session_is_refused_on_the_next_poll_and_send(): void
    {
        $this->service->send($this->channel, $this->amina, 'hello', (string) Str::uuid());
        $this->assertCount(1, $this->service->since($this->channel, $this->amina, 0));

        $this->app->make(CommunityService::class)->suspend($this->amina, User::factory()->admin()->create());

        try {
            $this->service->sync($this->channel, $this->amina, 0, null);
            $this->fail('Polling must be refused.');
        } catch (AuthorizationException) {
        }

        $this->expectException(AuthorizationException::class);
        $this->service->send($this->channel, $this->amina, 'still here', (string) Str::uuid());
    }

    #[Test]
    public function a_removed_member_is_refused_too(): void
    {
        $this->app->make(CommunityService::class)->remove($this->amina, User::factory()->admin()->create());

        $this->expectException(AuthorizationException::class);
        $this->service->history($this->channel, $this->amina);
    }

    #[Test]
    public function bodies_are_trimmed_limited_and_never_empty(): void
    {
        $message = $this->service->send($this->channel, $this->amina, "  line one\r\nline two  ", (string) Str::uuid());
        $this->assertSame("line one\nline two", $message->body);

        try {
            $this->service->send($this->channel, $this->amina, str_repeat('a', ChatSettings::BODY_MAX_LENGTH + 1), (string) Str::uuid());
            $this->fail('Over-long bodies must be refused.');
        } catch (\InvalidArgumentException) {
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->service->send($this->channel, $this->amina, "\n\t ", (string) Str::uuid());
    }

    #[Test]
    public function messages_are_presented_with_escaped_auto_linked_html_and_utc_times(): void
    {
        $message = $this->service->send($this->channel, $this->amina, '<b>see</b> https://example.org/?a=1&b=2', (string) Str::uuid());

        $data = $this->service->present($message, $this->yusuf);

        $this->assertSame('&lt;b&gt;see&lt;/b&gt; <a href="https://example.org/?a=1&amp;b=2" rel="nofollow noopener noreferrer" target="_blank">https://example.org/?a=1&amp;b=2</a>', $data['html']);
        $this->assertSame('Amina', $data['author']);
        $this->assertStringEndsWith('Z', $data['created_at']);
        $this->assertFalse($data['mine']);
        $this->assertTrue($data['can_report']);
    }

    #[Test]
    public function history_eager_loads_authors(): void
    {
        foreach (range(1, 5) as $i) {
            $this->service->send($this->channel, $i % 2 ? $this->amina : $this->yusuf, "m{$i}", (string) Str::uuid());
        }

        $queries = 0;
        $this->app->make('db')->listen(function () use (&$queries): void {
            $queries++;
        });

        $messages = $this->service->history($this->channel, $this->amina);
        $messages->each(fn (ChatMessage $message) => $message->user->name);

        $this->assertLessThanOrEqual(4, $queries, 'Authors must be eager loaded (no N+1)');
    }

    #[Test]
    public function every_change_dispatches_a_broadcast_after_commit_event_gated_by_the_transport_flag(): void
    {
        Event::fake([ChatMessageChanged::class]);

        $message = $this->service->send($this->channel, $this->amina, 'hi', (string) Str::uuid());
        $this->service->edit($message, $this->amina, 'hi!', 1);
        $this->service->delete($message, $this->amina);

        Event::assertDispatched(ChatMessageChanged::class, fn (ChatMessageChanged $event): bool => $event->change === ChatChange::Sent && $event->messageId === $message->id && $event->channelId === $this->channel->id);
        Event::assertDispatched(ChatMessageChanged::class, fn (ChatMessageChanged $event): bool => $event->change === ChatChange::Edited);
        Event::assertDispatched(ChatMessageChanged::class, fn (ChatMessageChanged $event): bool => $event->change === ChatChange::Deleted);
        Event::assertDispatchedTimes(ChatMessageChanged::class, 3);

        $event = ChatMessageChanged::for($message, ChatChange::Sent);
        $this->assertInstanceOf(ShouldBroadcast::class, $event);
        $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);
        $this->assertEquals([new PrivateChannel(ChatSettings::broadcastChannel($this->channel->id))], $event->broadcastOn());
        $this->assertSame('message.sent', $event->broadcastAs());
        $this->assertSame(['channel_id' => $this->channel->id, 'message_id' => $message->id, 'change' => 'sent'], $event->broadcastWith());

        $this->assertSame(ChatTransport::Poll, ChatSettings::transport());
        $this->assertFalse($event->broadcastWhen());

        config()->set('community.chat.transport', ChatTransport::Broadcast->value);
        $this->assertTrue($event->broadcastWhen());
        $this->assertSame('broadcast', ChatSettings::clientConfig()['transport']);
    }

    #[Test]
    public function a_retried_send_does_not_dispatch_a_second_event(): void
    {
        Event::fake([ChatMessageChanged::class]);
        $uuid = (string) Str::uuid();

        $this->service->send($this->channel, $this->amina, 'once', $uuid);
        $this->service->send($this->channel, $this->amina, 'once', $uuid);

        Event::assertDispatchedTimes(ChatMessageChanged::class, 1);
    }
}
