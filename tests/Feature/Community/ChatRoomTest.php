<?php

namespace Tests\Feature\Community;

use App\Constants\ChatSettings;
use App\Exceptions\ChatMessageConflictException;
use App\Livewire\Community\Chat\Room;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The chat room's Livewire actions as the browser script calls them.
 */
class ChatRoomTest extends TestCase
{
    use RefreshDatabase;

    private ChatChannel $channel;

    private User $member;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = ChatChannel::factory()->general()->create();
        $this->member = User::factory()->member()->create(['name' => 'Amina Hassan']);
        $this->other = User::factory()->member()->create(['name' => 'Yusuf Abdallah']);
    }

    /**
     * @return array<string, mixed>
     */
    private function returned(Testable $component): array
    {
        $returned = [];
        $component->assertReturned(function (array $value) use (&$returned): bool {
            $returned = $value;

            return true;
        });

        return $returned;
    }

    private function room(?User $as = null): Testable
    {
        return Livewire::actingAs($as ?? $this->member)->test(Room::class, ['channel' => $this->channel]);
    }

    private function send(User $author, string $body): ChatMessage
    {
        return app(ChatService::class)->send($this->channel, $author, $body, (string) Str::uuid());
    }

    /**
     * Bulk history straight through the factory (the service would rate-limit it).
     */
    private function seedMessage(User $author, string $body): ChatMessage
    {
        return ChatMessage::factory()->for($this->channel, 'channel')->for($author, 'user')->create(['body' => $body]);
    }

    #[Test]
    public function the_initial_state_holds_the_newest_page_in_id_order_with_cursors_and_channels(): void
    {
        $ids = [];
        foreach (range(1, 60) as $i) {
            $ids[] = $this->seedMessage($i % 2 ? $this->member : $this->other, "m{$i}")->id;
        }
        $otherChannel = ChatChannel::factory()->create(['name' => 'Soil science']);

        $component = $this->room();
        $initial = $component->get('initial');

        $this->assertCount(ChatSettings::PAGE_SIZE, $initial['messages']);
        $this->assertSame(array_slice($ids, 10), array_column($initial['messages'], 'id'));
        $this->assertTrue($initial['hasOlder']);
        $this->assertSame($initial['messages'][49]['id'], $initial['cursor']);
        $this->assertStringEndsWith('Z', $initial['since']);
        $this->assertSame(['General', 'Soil science'], array_column($initial['channels'], 'name'));
        $this->assertTrue($initial['channels'][0]['current']);
        $this->assertSame($otherChannel->slug, $initial['channels'][1]['slug']);
        $this->assertSame('Yusuf Abdallah', $initial['messages'][49]['author']);
    }

    #[Test]
    public function load_older_returns_the_page_before_a_message_and_says_when_history_is_exhausted(): void
    {
        $ids = [];
        foreach (range(1, 55) as $i) {
            $ids[] = $this->seedMessage($this->member, "m{$i}")->id;
        }

        $result = $this->returned($this->room()->call('loadOlder', $ids[5]));

        $this->assertTrue($result['ok']);
        $this->assertSame(array_slice($ids, 0, 5), array_column($result['messages'], 'id'));
        $this->assertFalse($result['hasOlder']);
    }

    #[Test]
    public function sync_returns_exactly_the_messages_after_the_cursor_and_advances_it(): void
    {
        $first = $this->send($this->member, 'one');
        $component = $this->room();
        $cursor = $component->get('initial')['cursor'];
        $this->assertSame($first->id, $cursor);

        $second = $this->send($this->other, 'two');
        $third = $this->send($this->other, 'three');

        $result = $this->returned($component->call('sync', $cursor, $component->get('initial')['since']));

        $this->assertTrue($result['ok']);
        $this->assertSame([$second->id, $third->id], array_column($result['messages'], 'id'));
        $this->assertSame([], $result['changed']);
        $this->assertSame($third->id, $result['cursor']);

        $again = $this->returned($component->call('sync', $result['cursor'], $result['since']));
        $this->assertSame([], $again['messages']);
    }

    #[Test]
    public function sync_also_reports_edits_and_deletions_of_messages_the_client_already_has(): void
    {
        $mine = $this->send($this->member, 'original');
        $theirs = $this->send($this->other, 'to be removed');
        $component = $this->room();
        $initial = $component->get('initial');

        Carbon::setTestNow(now()->addSeconds(2));
        app(ChatService::class)->edit($mine, $this->member, 'changed', 1);
        app(ChatService::class)->delete($theirs, $this->other);

        $result = $this->returned($component->call('sync', $initial['cursor'], $initial['since']));

        $this->assertSame([], $result['messages']);
        $changed = collect($result['changed'])->keyBy('id');
        $this->assertSame('changed', $changed[$mine->id]['body']);
        $this->assertSame(2, $changed[$mine->id]['version']);
        $this->assertTrue($changed[$theirs->id]['deleted']);
        $this->assertSame('', $changed[$theirs->id]['body']);

        Carbon::setTestNow();
    }

    #[Test]
    public function send_returns_the_saved_message_and_everything_that_arrived_since_the_cursor(): void
    {
        $component = $this->room();
        $initial = $component->get('initial');
        $theirs = $this->send($this->other, 'just before');

        $uuid = (string) Str::uuid();
        $result = $this->returned($component->call('send', "  Hello\r\nworld  ", $uuid, $initial['cursor'], $initial['since']));

        $this->assertTrue($result['ok']);
        $this->assertSame("Hello\nworld", $result['message']['body']);
        $this->assertSame($uuid, $result['message']['client_uuid']);
        $this->assertSame([$theirs->id, $result['message']['id']], array_column($result['sync']['messages'], 'id'));
        $this->assertSame($result['message']['id'], $result['sync']['cursor']);
    }

    #[Test]
    public function resending_the_same_client_uuid_creates_only_one_message(): void
    {
        $uuid = (string) Str::uuid();
        $component = $this->room();

        $first = $this->returned($component->call('send', 'once', $uuid, 0, null));
        $second = $this->returned($component->call('send', 'once (retry)', $uuid, 0, null));

        $this->assertSame($first['message']['id'], $second['message']['id']);
        $this->assertSame('once', $second['message']['body']);
        $this->assertSame(1, ChatMessage::query()->count());
    }

    #[Test]
    public function empty_too_long_and_malformed_sends_are_refused_with_a_message(): void
    {
        $component = $this->room();

        $this->assertSame(Room::ERROR_INVALID, $this->returned($component->call('send', "   \n", (string) Str::uuid(), 0, null))['error']);
        $this->assertSame(Room::ERROR_INVALID, $this->returned($component->call('send', str_repeat('x', ChatSettings::BODY_MAX_LENGTH + 1), (string) Str::uuid(), 0, null))['error']);
        $this->assertSame(Room::ERROR_INVALID, $this->returned($component->call('send', 'hi', 'not-a-uuid', 0, null))['error']);
        $this->assertSame(0, ChatMessage::query()->count());
    }

    #[Test]
    public function sends_are_rate_limited_per_member(): void
    {
        $component = $this->room();

        foreach (range(1, ChatSettings::SEND_LIMIT_PER_MINUTE) as $i) {
            $this->assertTrue($this->returned($component->call('send', "m{$i}", (string) Str::uuid(), 0, null))['ok']);
        }

        $result = $this->returned($component->call('send', 'one too many', (string) Str::uuid(), 0, null));

        $this->assertFalse($result['ok']);
        $this->assertSame(Room::ERROR_RATE_LIMIT, $result['error']);
        $this->assertGreaterThan(0, $result['retry_after']);
        $this->assertSame(ChatSettings::SEND_LIMIT_PER_MINUTE, ChatMessage::query()->count());

        // Another member is not affected.
        $this->assertTrue($this->returned($this->room($this->other)->call('send', 'fine', (string) Str::uuid(), 0, null))['ok']);
    }

    #[Test]
    public function a_stale_edit_is_rejected_and_the_message_can_be_reloaded(): void
    {
        $message = $this->send($this->member, 'first');
        app(ChatService::class)->edit($message, $this->member, 'second', 1); // another tab

        $component = $this->room();
        $result = $this->returned($component->call('edit', $message->id, 'third', 1));

        $this->assertFalse($result['ok']);
        $this->assertSame(Room::ERROR_CONFLICT, $result['error']);
        $this->assertSame(ChatMessageConflictException::MESSAGE, $result['message']);
        $this->assertSame('second', $message->fresh()->body);

        $reloaded = $this->returned($component->call('reload', $message->id));
        $this->assertSame('second', $reloaded['message']['body']);
        $this->assertSame(2, $reloaded['message']['version']);

        $this->assertTrue($this->returned($component->call('edit', $message->id, 'third', 2))['ok']);
        $this->assertSame('third', $message->fresh()->body);
    }

    #[Test]
    public function editing_or_reporting_a_deleted_message_fails_gracefully(): void
    {
        $mine = $this->send($this->member, 'mine');
        $theirs = $this->send($this->other, 'theirs');
        app(ChatService::class)->delete($mine, $this->member);
        app(ChatService::class)->delete($theirs, $this->other);

        $component = $this->room();

        $edit = $this->returned($component->call('edit', $mine->id, 'again', 1));
        $this->assertSame(Room::ERROR_REMOVED, $edit['error']);
        $this->assertSame('This message has been removed.', $edit['message']);

        $report = $this->returned($component->call('report', $theirs->id, 'Rude'));
        $this->assertSame(Room::ERROR_REMOVED, $report['error']);

        $missing = $this->returned($component->call('edit', 999999, 'ghost', 1));
        $this->assertSame(Room::ERROR_REMOVED, $missing['error']);

        $this->assertDatabaseCount('chat_reports', 0);
    }

    #[Test]
    public function members_edit_and_delete_only_their_own_messages_and_report_only_others(): void
    {
        $mine = $this->send($this->member, 'mine');
        $theirs = $this->send($this->other, 'theirs');
        $component = $this->room();

        $this->assertSame(Room::ERROR_ACCESS, $this->returned($component->call('edit', $theirs->id, 'hijack', 1))['error']);
        $this->assertSame(Room::ERROR_ACCESS, $this->returned($component->call('remove', $theirs->id))['error']);
        $this->assertSame(Room::ERROR_ACCESS, $this->returned($component->call('report', $mine->id, 'myself'))['error']);
        $component->assertSet('accessRevoked', false);

        $edited = $this->returned($component->call('edit', $mine->id, 'mine, edited', 1));
        $this->assertTrue($edited['ok']);
        $this->assertNotNull($edited['message']['edited_at']);

        $reported = $this->returned($component->call('report', $theirs->id, '  Off topic  '));
        $this->assertTrue($reported['ok']);
        $this->assertDatabaseHas('chat_reports', ['message_id' => $theirs->id, 'reporter_id' => $this->member->id, 'reason' => 'Off topic']);

        $removed = $this->returned($component->call('remove', $mine->id));
        $this->assertTrue($removed['ok']);
        $this->assertTrue($removed['message']['deleted']);
        $this->assertSoftDeleted('chat_messages', ['id' => $mine->id]);
    }

    #[Test]
    public function admins_delete_anyones_message_and_it_shows_as_removed_to_everyone(): void
    {
        $theirs = $this->send($this->other, 'theirs');
        $admin = User::factory()->admin()->create();

        $removed = $this->returned($this->room($admin)->call('remove', $theirs->id));

        $this->assertTrue($removed['ok'] && $removed['message']['deleted']);

        $initial = $this->room()->get('initial');
        $this->assertTrue($initial['messages'][0]['deleted']);
        $this->assertSame('', $initial['messages'][0]['html']);
    }

    #[Test]
    public function mark_read_only_ever_moves_forward(): void
    {
        $ids = array_map(fn (int $i): int => $this->send($this->other, "m{$i}")->id, range(1, 5));
        $component = $this->room();

        $component->call('markRead', $ids[4]);
        $this->assertDatabaseHas('chat_reads', ['channel_id' => $this->channel->id, 'user_id' => $this->member->id, 'last_read_message_id' => $ids[4]]);

        $component->call('markRead', $ids[1]);
        $this->assertDatabaseHas('chat_reads', ['channel_id' => $this->channel->id, 'user_id' => $this->member->id, 'last_read_message_id' => $ids[4]]);

        $this->assertSame(0, app(ChatService::class)->unreadCount($this->channel, $this->member));
    }

    #[Test]
    public function the_page_passes_the_transport_config_and_the_unread_cursor_to_the_script(): void
    {
        $this->send($this->other, 'unread one');
        $this->send($this->other, 'unread two');

        $initial = $this->room()->get('initial');
        $this->assertSame(0, $initial['lastRead']);

        $config = ChatSettings::clientConfig();
        $this->assertSame('poll', $config['transport']);
        $this->assertSame([5, 10, 30], $config['backoff']);
        $this->assertSame(ChatSettings::BODY_MAX_LENGTH, $config['bodyMaxLength']);

        $html = $this->actingAs($this->member)->get(route('community.chat.channel', $this->channel))->assertOk()
            ->assertSee('data-chat-room', false)
            ->assertSee('chatRoom({', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/transport.{1,40}?poll/', $html);
        $this->assertMatchesRegularExpression('/pollInterval.{1,40}?'.ChatSettings::pollIntervalSeconds().'/', $html);
    }
}
