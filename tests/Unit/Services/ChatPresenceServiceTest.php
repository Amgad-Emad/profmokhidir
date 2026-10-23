<?php

namespace Tests\Unit\Services;

use App\Constants\ChatSettings;
use App\Models\ChatChannel;
use App\Models\User;
use App\Services\ChatCorrespondenceService;
use App\Services\ChatService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * "Online now" from read-cursor activity: the page load and the existing poll
 * record presence, nobody is online after the window, and the payloads carry
 * the online ids and every member's initials.
 */
class ChatPresenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $chat;

    private ChatCorrespondenceService $service;

    private ChatChannel $channel;

    private User $member;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chat = $this->app->make(ChatService::class);
        $this->service = $this->app->make(ChatCorrespondenceService::class);
        $this->channel = ChatChannel::factory()->general()->create();
        $this->member = User::factory()->member()->create(['name' => 'Amina Hassan']);
        $this->other = User::factory()->member()->create(['name' => 'Yusuf Abdallah']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function a_member_is_online_after_noting_presence_until_the_window_passes(): void
    {
        Carbon::setTestNow('2026-10-23 10:00:00');
        $this->service->notePresence($this->channel, $this->member);

        $this->assertSame([$this->member->id], $this->service->online());

        $later = CarbonImmutable::parse('2026-10-23 10:00:00')->addSeconds(ChatSettings::ONLINE_WINDOW_SECONDS + 1);
        $this->assertSame([], $this->service->online($later));
    }

    #[Test]
    public function the_initial_state_and_every_poll_record_presence_and_return_who_is_online(): void
    {
        Carbon::setTestNow('2026-10-23 10:00:00');
        $initial = $this->chat->initialState($this->channel, $this->member);
        $this->assertSame([$this->member->id], $initial['online']);

        Carbon::setTestNow('2026-10-23 10:01:00');
        $sync = $this->chat->sync($this->channel, $this->other, 0, null);
        $this->assertSame([$this->member->id, $this->other->id], $sync['online']);

        // Ten minutes on, only whoever is still polling remains online.
        Carbon::setTestNow('2026-10-23 10:10:00');
        $sync = $this->chat->sync($this->channel, $this->other, 0, null);
        $this->assertSame([$this->other->id], $sync['online']);
    }

    #[Test]
    public function presence_never_moves_the_read_cursor(): void
    {
        $message = $this->chat->send($this->channel, $this->other, 'Hello', (string) Str::uuid());

        $this->chat->sync($this->channel, $this->member, 0, null);

        $this->assertSame(1, $this->chat->unreadCount($this->channel, $this->member));
        $this->assertSame(0, $this->chat->unreadCount($this->channel, $this->other));
        $this->assertNotNull($message->id);
    }

    #[Test]
    public function someone_without_chat_access_cannot_record_presence(): void
    {
        $suspended = User::factory()->suspendedMember()->create();

        $this->expectException(AuthorizationException::class);

        $this->service->notePresence($this->channel, $suspended);
    }

    #[Test]
    public function members_and_messages_carry_initials_for_the_ring_avatar(): void
    {
        $this->chat->send($this->channel, $this->other, 'Hello', (string) Str::uuid());

        $members = collect($this->service->members())->pluck('initials', 'name')->all();
        $this->assertSame(['Amina Hassan' => 'AH', 'Yusuf Abdallah' => 'YA'], $members);

        $initial = $this->chat->initialState($this->channel, $this->member);
        $this->assertSame('YA', $initial['messages'][0]['initials']);
    }
}
