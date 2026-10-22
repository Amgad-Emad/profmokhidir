<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\ChatChange;
use App\Constants\ChatSettings;
use App\Events\ChatMessageChanged;
use App\Exceptions\ChatMessageRemovedException;
use App\Models\AuditLog;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatCorrespondenceService;
use App\Services\ChatService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Replies, pins, the members list and the permalink page: the correspondence
 * features added on top of the reliability rules.
 */
class ChatCorrespondenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $chat;

    private ChatCorrespondenceService $service;

    private ChatChannel $channel;

    private User $member;

    private User $other;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chat = $this->app->make(ChatService::class);
        $this->service = $this->app->make(ChatCorrespondenceService::class);
        $this->channel = ChatChannel::factory()->general()->create();
        $this->member = User::factory()->member()->create(['name' => 'Amina Hassan', 'affiliation' => 'University of Khartoum']);
        $this->other = User::factory()->member()->create(['name' => 'Yusuf Abdallah', 'affiliation' => 'University of Gezira']);
        $this->admin = User::factory()->admin()->create();
    }

    private function send(User $author, string $body, ?int $replyTo = null): ChatMessage
    {
        return $this->chat->send($this->channel, $author, $body, (string) Str::uuid(), $replyTo);
    }

    /* ---------------------------------------------------------- replies */

    #[Test]
    public function a_reply_names_the_message_it_answers_and_is_presented_with_an_excerpt(): void
    {
        $original = $this->send($this->member, '**Has anyone** kept the AGR 301 problem sets? See https://example.org/x');
        $reply = $this->send($this->other, 'I have the 1994 set.', $original->id);

        $this->assertSame($original->id, $reply->reply_to_id);

        $data = $this->chat->present($this->chat->findMessage($reply->id), $this->member);

        $this->assertSame($original->id, $data['reply_to']['id']);
        $this->assertSame('Amina Hassan', $data['reply_to']['author']);
        $this->assertSame('Has anyone kept the AGR 301 problem sets? See https://example.org/x', $data['reply_to']['excerpt']);
        $this->assertFalse($data['reply_to']['deleted']);
        $this->assertNull($this->chat->present($this->chat->findMessage($original->id), $this->member)['reply_to']);
    }

    #[Test]
    public function a_reply_to_a_removed_message_shows_the_removed_placeholder(): void
    {
        $original = $this->send($this->member, 'to be removed');
        $reply = $this->send($this->other, 'answer', $original->id);
        $this->chat->delete($original, $this->member);

        $data = $this->chat->present($this->chat->findMessage($reply->id), $this->other);

        $this->assertTrue($data['reply_to']['deleted']);
        $this->assertSame('Original message removed', $data['reply_to']['excerpt']);
        $this->assertSame('', $data['reply_to']['author']);
        $this->assertSame($original->id, $data['reply_to']['id']);
    }

    #[Test]
    public function a_reply_must_answer_a_message_in_the_same_channel(): void
    {
        $elsewhere = ChatMessage::factory()->for(ChatChannel::factory()->create(), 'channel')->for($this->other, 'user')->create();

        try {
            $this->send($this->member, 'wrong room', $elsewhere->id);
            $this->fail('A reply across channels must be refused');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('not in this channel', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->send($this->member, 'ghost', 999999);
    }

    #[Test]
    public function the_factory_reply_state_keeps_the_original_channel(): void
    {
        $original = ChatMessage::factory()->for($this->channel, 'channel')->for($this->member, 'user')->create();
        $reply = ChatMessage::factory()->for($this->other, 'user')->replyTo($original)->create();

        $this->assertSame($this->channel->id, $reply->channel_id);
        $this->assertTrue($reply->replyTo->is($original));
    }

    /* ---------------------------------------------------------- pins */

    #[Test]
    public function moderators_pin_and_unpin_with_an_audit_entry_and_a_broadcast_nudge(): void
    {
        Event::fake([ChatMessageChanged::class]);
        $message = $this->send($this->member, 'Welcome, friends');

        $pinned = $this->service->pin($message, $this->admin);

        $this->assertTrue($pinned->isPinned());
        $this->assertSame($this->admin->id, $pinned->pinned_by);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::CHAT_MESSAGE_PINNED, 'user_id' => $this->admin->id, 'subject_id' => $message->id]);
        Event::assertDispatched(ChatMessageChanged::class, fn (ChatMessageChanged $event): bool => $event->change === ChatChange::Pinned && $event->messageId === $message->id);

        $pins = $this->service->pins($this->channel, $this->member);
        $this->assertCount(1, $pins);
        $this->assertSame('Amina Hassan', $pins[0]['author']);
        $this->assertSame('Welcome, friends', $pins[0]['excerpt']);
        $this->assertSame($this->admin->name, $pins[0]['pinned_by']);

        $this->service->pin($message, $this->admin); // a second pin is a no-op
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CHAT_MESSAGE_PINNED)->count());

        $this->service->unpin($message, $this->admin);

        $this->assertFalse($message->fresh()->isPinned());
        $this->assertNull($message->fresh()->pinned_by);
        $this->assertSame([], $this->service->pins($this->channel, $this->member));
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::CHAT_MESSAGE_UNPINNED, 'subject_id' => $message->id]);
        Event::assertDispatched(ChatMessageChanged::class, fn (ChatMessageChanged $event): bool => $event->change === ChatChange::Unpinned);
    }

    #[Test]
    public function members_cannot_pin_and_removed_messages_cannot_be_pinned(): void
    {
        $message = $this->send($this->member, 'mine');

        try {
            $this->service->pin($message, $this->member);
            $this->fail('Members must not pin');
        } catch (AuthorizationException) {
        }

        $this->chat->delete($message, $this->member);

        $this->expectException(ChatMessageRemovedException::class);
        $this->service->pin($message, $this->admin);
    }

    #[Test]
    public function pinning_does_not_count_as_an_edit_and_a_deleted_pin_leaves_the_list(): void
    {
        $message = $this->send($this->member, 'pinned then removed');
        $state = $this->chat->initialState($this->channel, $this->other);

        $this->travel(2)->seconds();
        $this->service->pin($message, $this->admin);

        $sync = $this->chat->sync($this->channel, $this->other, $state['cursor'], $state['since']);
        $this->assertSame([], $sync['changed'], 'A pin must not appear as an edit');
        $this->assertSame([$message->id], array_column($sync['pins'], 'id'));

        $presented = $this->chat->present($this->chat->findMessage($message->id), $this->admin);
        $this->assertTrue($presented['pinned']);
        $this->assertTrue($presented['can_pin']);
        $this->assertFalse($this->chat->present($this->chat->findMessage($message->id), $this->other)['can_pin']);

        $this->chat->delete($message, $this->admin);
        $this->assertSame([], $this->service->pins($this->channel, $this->other));
    }

    /* ---------------------------------------------------------- members and state */

    #[Test]
    public function the_members_list_holds_approved_members_by_name(): void
    {
        User::factory()->applicant()->create(['name' => 'Aaron Applicant']);
        User::factory()->suspendedMember()->create(['name' => 'Aaron Suspended']);

        $members = $this->service->members();

        $this->assertSame(['Amina Hassan', 'Yusuf Abdallah'], array_column($members, 'name'));
        $this->assertSame('University of Gezira', $members[1]['affiliation']);
    }

    #[Test]
    public function the_initial_state_carries_pins_members_numerals_and_the_linked_message_page(): void
    {
        ChatChannel::factory()->create(['name' => 'Soil science']);
        $ids = [];
        foreach (range(1, 120) as $i) {
            $ids[] = ChatMessage::factory()->for($this->channel, 'channel')->for($i % 2 ? $this->member : $this->other, 'user')->create(['body' => "m{$i}"])->id;
        }
        $this->service->pin(ChatMessage::query()->find($ids[3]), $this->admin);

        $plain = $this->chat->initialState($this->channel, $this->member);
        $this->assertNull($plain['focus']);
        $this->assertSame([$ids[3]], array_column($plain['pins'], 'id'));
        $this->assertSame(['Amina Hassan', 'Yusuf Abdallah'], array_column($plain['members'], 'name'));
        $this->assertSame(['I', 'II'], array_column($plain['channels'], 'numeral'));
        $this->assertSame(array_slice($ids, 70), array_column($plain['messages'], 'id'));

        $focused = $this->chat->initialState($this->channel, $this->member, $ids[40]);
        $half = intdiv(ChatSettings::PAGE_SIZE, 2);
        $this->assertSame($ids[40], $focused['focus']);
        $this->assertSame(array_slice($ids, 41 - $half, $half * 2), array_column($focused['messages'], 'id'));
        $this->assertTrue($focused['hasOlder']);
        $this->assertSame($ids[40 + $half], $focused['cursor']);

        $sync = $this->chat->sync($this->channel, $this->member, $focused['cursor'], $focused['since']);
        $this->assertSame(array_slice($ids, 41 + $half), array_column($sync['messages'], 'id'));

        $elsewhere = ChatMessage::factory()->for(ChatChannel::factory()->create(), 'channel')->for($this->other, 'user')->create();
        $this->assertNull($this->chat->initialState($this->channel, $this->member, $elsewhere->id)['focus']);
        $this->assertNull($this->chat->initialState($this->channel, $this->member, 999999)['focus']);
    }

    #[Test]
    public function search_refuses_short_terms_and_non_members(): void
    {
        $this->assertSame(['term' => 'a', 'messages' => []], $this->service->search($this->channel, $this->member, ' a '));
        $this->assertSame('', $this->service->search($this->channel, $this->member, "  \n")['term']);

        $this->expectException(AuthorizationException::class);
        $this->service->search($this->channel, User::factory()->applicant()->create(), 'sorghum');
    }

    #[Test]
    public function pins_and_members_need_chat_access(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->service->pins($this->channel, User::factory()->suspendedMember()->create());
    }
}
