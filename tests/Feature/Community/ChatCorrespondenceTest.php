<?php

namespace Tests\Feature\Community;

use App\Constants\AuditAction;
use App\Constants\ChatCopy;
use App\Constants\ChatSettings;
use App\Livewire\Community\Chat\Room;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatCorrespondenceService;
use App\Services\ChatService;
use App\Services\CommunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The correspondence room's page and the Livewire actions added for it:
 * replies, pins, search, permalinks and the markup the script and assistive
 * technology rely on. Access rules are unchanged and still enforced server side.
 */
class ChatCorrespondenceTest extends TestCase
{
    use RefreshDatabase;

    private ChatChannel $channel;

    private User $member;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = ChatChannel::factory()->general()->create();
        $this->member = User::factory()->member()->create(['name' => 'Amina Hassan', 'affiliation' => 'University of Khartoum']);
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
        return Livewire::actingAs($as ?? $this->member)->test(Room::class);
    }

    private function send(User $author, string $body): ChatMessage
    {
        return app(ChatService::class)->send($this->channel, $author, $body, (string) Str::uuid());
    }

    #[Test]
    public function the_page_is_laid_out_as_a_correspondence_room(): void
    {
        $this->send($this->other, 'Welcome, friends');

        $html = $this->actingAs($this->member)->get(route('community.chat'))->assertOk()
            ->assertDontSee('data-chat-rail', false)
            ->assertSee('data-chat-conversation', false)
            ->assertSee('data-chat-panel', false)
            ->assertSee('role="log" aria-live="polite"', false)
            ->assertSee('placeholder="'.ChatCopy::COMPOSER_PLACEHOLDER.'"', false)
            ->assertSee(ChatCopy::COMPOSER_HINT)
            ->assertSee(ChatCopy::PANEL_TITLE)
            ->assertSee(ChatCopy::PANEL_CONDUCT)
            ->assertSee(ChatCopy::PANEL_PINNED)
            ->assertSee(ChatCopy::PANEL_MEMBERS)
            ->assertSee(ChatCopy::EMPTY_EPIGRAPH)
            ->assertSee('data-reply-preview', false)
            ->assertSee('data-offline-banner', false)
            ->assertSee('data-search-toggle', false)
            ->assertSee('Amina Hassan')
            ->assertSee('University of Khartoum')
            ->assertSee(ChatCopy::STATUS_MEMBER)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('interactive-widget=resizes-content', false)
            ->assertDontSee('katex.min.css">', false)
            ->getContent();

        $this->assertStringContainsString($this->inPageJson('"numeral":"I"'), $html);
        $this->assertStringContainsString($this->inPageJson('"katexStylesheet"'), $html);
        $this->assertStringContainsString($this->inPageJson('"members":['), $html);
        $this->assertStringContainsString($this->inPageJson('"pins":[]'), $html);
    }

    /**
     * @js() prints JSON with quotes as \u0022, so page assertions need the same form.
     */
    private function inPageJson(string $fragment): string
    {
        return str_replace('"', '\u0022', $fragment);
    }

    #[Test]
    public function an_administrator_sees_their_status_in_the_member_menu(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('community.chat'))->assertOk()
            ->assertSee(ChatCopy::STATUS_ADMIN);
    }

    #[Test]
    public function a_permalink_focuses_the_linked_message_in_the_initial_state(): void
    {
        $ids = [];
        foreach (range(1, 80) as $i) {
            $ids[] = ChatMessage::factory()->for($this->channel, 'channel')->for($this->other, 'user')->create(['body' => "m{$i}"])->id;
        }

        // Without the parameter the newest page is loaded (checked first: query params stick to the shared request in tests).
        $this->assertNull($this->room()->get('initial')['focus']);

        $initial = $this->actingAs($this->member)
            ->get(route('community.chat').'?'.ChatSettings::MESSAGE_PARAM.'='.$ids[10])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($this->inPageJson('"focus":'.$ids[10]), $initial);

        $component = Livewire::actingAs($this->member)->withQueryParams([ChatSettings::MESSAGE_PARAM => $ids[10]])->test(Room::class);
        $state = $component->get('initial');
        $this->assertSame($ids[10], $state['focus']);
        $this->assertContains($ids[10], array_column($state['messages'], 'id'));
        $this->assertContains($ids[0], array_column($state['messages'], 'id'));

        $this->assertNull(Livewire::actingAs($this->member)->withQueryParams([ChatSettings::MESSAGE_PARAM => 'abc'])->test(Room::class)->get('initial')['focus']);
    }

    #[Test]
    public function send_accepts_a_reply_target_from_this_channel_only(): void
    {
        $original = $this->send($this->other, 'Has anyone kept the problem sets?');
        $component = $this->room();

        $result = $this->returned($component->call('send', 'I have them.', (string) Str::uuid(), 0, null, $original->id));

        $this->assertTrue($result['ok']);
        $this->assertSame($original->id, $result['message']['reply_to']['id']);
        $this->assertSame('Yusuf Abdallah', $result['message']['reply_to']['author']);
        $this->assertSame('Has anyone kept the problem sets?', $result['message']['reply_to']['excerpt']);
        $this->assertDatabaseHas('chat_messages', ['body' => 'I have them.', 'reply_to_id' => $original->id]);

        $elsewhere = ChatMessage::factory()->for(ChatChannel::factory()->create(), 'channel')->for($this->other, 'user')->create();
        $refused = $this->returned($component->call('send', 'wrong room', (string) Str::uuid(), 0, null, $elsewhere->id));
        $this->assertSame(Room::ERROR_INVALID, $refused['error']);
        $this->assertDatabaseMissing('chat_messages', ['body' => 'wrong room']);

        $this->assertSame(Room::ERROR_INVALID, $this->returned($component->call('send', 'zero', (string) Str::uuid(), 0, null, 0))['error']);
    }

    #[Test]
    public function a_reply_to_a_message_removed_later_shows_the_placeholder_to_every_viewer(): void
    {
        $original = $this->send($this->other, 'to be removed');
        $component = $this->room();
        $reply = $this->returned($component->call('send', 'answer', (string) Str::uuid(), 0, null, $original->id));

        app(ChatService::class)->delete($original, $this->other);

        $initial = $this->room($this->other)->get('initial');
        $presented = collect($initial['messages'])->firstWhere('id', $reply['message']['id']);

        $this->assertTrue($presented['reply_to']['deleted']);
        $this->assertSame(ChatCopy::REPLY_REMOVED, $presented['reply_to']['excerpt']);
    }

    #[Test]
    public function only_moderators_pin_and_the_pin_list_comes_back_with_the_message(): void
    {
        $message = $this->send($this->other, 'Welcome, friends');
        $admin = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('pin', $message));
        $this->assertFalse(Gate::forUser($this->member)->allows('pin', $message));
        $this->assertFalse(Gate::forUser($this->other)->allows('pin', $message));

        $refused = $this->returned($this->room()->call('pin', $message->id));
        $this->assertSame(Room::ERROR_ACCESS, $refused['error']);
        $this->assertNull($message->fresh()->pinned_at);

        $pinned = $this->returned($this->room($admin)->call('pin', $message->id));
        $this->assertTrue($pinned['ok']);
        $this->assertTrue($pinned['message']['pinned']);
        $this->assertSame([$message->id], array_column($pinned['pins'], 'id'));
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::CHAT_MESSAGE_PINNED, 'user_id' => $admin->id, 'subject_id' => $message->id]);

        // Members see the pin on their next poll and on the first render.
        $sync = $this->returned($this->room()->call('sync', $message->id, null));
        $this->assertSame([$message->id], array_column($sync['pins'], 'id'));
        $this->assertSame('Yusuf Abdallah', $this->room()->get('initial')['pins'][0]['author']);

        $unpinned = $this->returned($this->room($admin)->call('unpin', $message->id));
        $this->assertTrue($unpinned['ok']);
        $this->assertFalse($unpinned['message']['pinned']);
        $this->assertSame([], $unpinned['pins']);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::CHAT_MESSAGE_UNPINNED, 'subject_id' => $message->id]);

        app(ChatService::class)->delete($message, $admin);
        $this->assertSame(Room::ERROR_REMOVED, $this->returned($this->room($admin)->call('pin', $message->id))['error']);
    }

    #[Test]
    public function a_suspended_member_cannot_search_or_read_pins(): void
    {
        $message = $this->send($this->other, 'pinned');
        app(ChatCorrespondenceService::class)->pin($message, User::factory()->admin()->create());
        $component = $this->room();

        app(CommunityService::class)->suspend($this->member, User::factory()->admin()->create(), 'Conduct');

        $this->assertSame(Room::ERROR_ACCESS, $this->returned($component->call('search', 'pinned'))['error']);
        $component->assertSet('accessRevoked', true);
    }

    #[Test]
    public function search_returns_nothing_for_short_terms_and_needs_access(): void
    {
        $result = $this->returned($this->room()->call('search', ' a '));

        $this->assertTrue($result['ok']);
        $this->assertSame([], $result['messages']);
        $this->assertSame('a', $result['term']);
    }

    #[Test]
    public function the_seeded_conversation_includes_a_reply_and_a_pin(): void
    {
        $this->seed();

        $channel = app(ChatService::class)->findChannel('general');
        $this->assertNotNull($channel);
        $this->assertSame(1, ChatMessage::query()->where('channel_id', $channel->id)->whereNotNull('reply_to_id')->count());
        $this->assertSame(1, ChatMessage::query()->where('channel_id', $channel->id)->whereNotNull('pinned_at')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::CHAT_MESSAGE_PINNED]);
    }
}
