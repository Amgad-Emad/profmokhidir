<?php

namespace Tests\Feature\Community;

use App\Constants\ChatSettings;
use App\Livewire\Community\Chat\Room;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\CommunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Who may open the chat page and call its Livewire actions. Membership is
 * checked by the route middleware, by the policies and by ChatService, so a
 * direct Livewire request without the page cannot get around it either.
 */
class ChatAccessTest extends TestCase
{
    use RefreshDatabase;

    private ChatChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = ChatChannel::factory()->general()->create();
    }

    #[Test]
    public function logged_out_visitors_are_redirected_to_sign_in(): void
    {
        $this->get(route('community.chat'))->assertRedirect(route('login'));
        $this->get(route('community.chat'))->assertRedirect(route('login'));
    }

    #[Test]
    public function applicants_rejected_and_suspended_users_cannot_load_the_chat_page(): void
    {
        foreach ([User::factory()->applicant(), User::factory()->rejectedApplicant(), User::factory()->suspendedMember(), User::factory()->editor()] as $factory) {
            $user = $factory->create();

            $this->actingAs($user)->get(route('community.chat'))->assertRedirect(route('community'));
            $this->actingAs($user)->get(route('community.chat'))->assertRedirect(route('community'));
        }
    }

    #[Test]
    public function applicants_rejected_and_suspended_users_cannot_mount_the_component_or_call_send_directly(): void
    {
        foreach ([User::factory()->applicant(), User::factory()->rejectedApplicant(), User::factory()->suspendedMember(), User::factory()->editor()] as $factory) {
            Livewire::actingAs($factory->create())->test(Room::class)->assertForbidden();
        }

        $this->assertSame(0, ChatMessage::query()->count());
    }

    #[Test]
    public function a_direct_send_from_a_user_without_access_creates_nothing_even_when_the_component_state_is_forged(): void
    {
        // A member mounts legitimately, then their standing changes before the next request.
        $member = User::factory()->member()->create();
        $component = Livewire::actingAs($member)->test(Room::class);

        app(CommunityService::class)->reject(User::factory()->applicant()->create(), User::factory()->admin()->create());
        app(CommunityService::class)->remove($member, User::factory()->admin()->create());

        $component->call('send', 'forged', (string) Str::uuid(), 0, null)
            ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['error'] === Room::ERROR_ACCESS);

        $this->assertDatabaseMissing('chat_messages', ['body' => 'forged']);
    }

    #[Test]
    public function an_approved_member_can_read_and_send(): void
    {
        $member = User::factory()->member()->create();
        $other = User::factory()->member()->create(['name' => 'Yusuf Abdallah', 'affiliation' => 'Gezira']);
        ChatMessage::factory()->for($this->channel, 'channel')->for($other, 'user')->create(['body' => 'Welcome, friends']);

        // The old channel addresses move permanently to the one room.
        $this->actingAs($member)->get('/community/chat/general')->assertStatus(301)->assertRedirect(route('community.chat'));

        $this->actingAs($member)->get(route('community.chat'))->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('wire:poll.'.ChatSettings::pollIntervalSeconds().'s', false)
            ->assertSee('Welcome, friends')
            ->assertSee('Yusuf Abdallah')
            ->assertSee('Gezira')
            ->assertSee('livewire', false);

        $uuid = (string) Str::uuid();

        Livewire::actingAs($member)->test(Room::class)
            ->call('send', 'Hello from a member', $uuid, 0, null)
            ->assertReturned(fn (array $result): bool => $result['ok'] === true && $result['message']['body'] === 'Hello from a member' && $result['message']['mine'] === true);

        $this->assertDatabaseHas('chat_messages', ['channel_id' => $this->channel->id, 'user_id' => $member->id, 'client_uuid' => $uuid, 'body' => 'Hello from a member']);
    }

    #[Test]
    public function admins_can_read_and_moderate(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('community.chat'))->assertOk();

        Livewire::actingAs($admin)->test(Room::class)
            ->call('sync', 0, null)
            ->assertReturned(fn (array $result): bool => $result['ok'] === true);
    }

    #[Test]
    public function a_suspended_member_loses_access_on_the_very_next_poll_or_send(): void
    {
        $member = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $component = Livewire::actingAs($member)->test(Room::class)
            ->call('sync', 0, null)
            ->assertReturned(fn (array $result): bool => $result['ok'] === true)
            ->assertSet('accessRevoked', false);

        app(CommunityService::class)->suspend($member, $admin, 'Conduct');

        $component->call('sync', 0, null)
            ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['error'] === Room::ERROR_ACCESS)
            ->assertSet('accessRevoked', true)
            ->assertSee('Your membership is suspended');

        $component->call('send', 'Still here?', (string) Str::uuid(), 0, null)
            ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['error'] === Room::ERROR_ACCESS);

        $this->assertDatabaseMissing('chat_messages', ['body' => 'Still here?']);

        // And the page itself is now refused.
        $this->actingAs($member)->get(route('community.chat'))->assertRedirect(route('community'));
    }

    #[Test]
    public function the_room_is_opened_on_first_use_when_no_channel_exists(): void
    {
        $this->channel->delete();

        $this->actingAs(User::factory()->member()->create())->get(route('community.chat'))->assertOk()
            ->assertSee(ChatSettings::ROOM_NAME)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->assertSame([ChatSettings::ROOM_SLUG], ChatChannel::query()->pluck('slug')->all());
    }
}
