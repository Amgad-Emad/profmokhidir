<?php

namespace Tests\Feature\Community;

use App\Constants\AdminNavigation;
use App\Constants\ChatCopy;
use App\Constants\ChatSettings;
use App\Constants\CommunityStatus;
use App\Constants\SettingKeys;
use App\Livewire\Admin\Settings\SiteSettings;
use App\Livewire\Community\Chat\Room;
use App\Models\ChatChannel;
use App\Models\User;
use App\Services\ChatService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatOneRoomTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = User::factory()->member()->create(['name' => 'Amina Hassan', 'affiliation' => 'University of Khartoum']);
    }

    #[Test]
    public function the_room_is_the_scientific_community_channel_else_the_oldest_else_a_new_one(): void
    {
        $chat = $this->app->make(ChatService::class);
        $this->assertSame(ChatSettings::ROOM_SLUG, $chat->room()->slug);
        $this->assertSame(1, ChatChannel::query()->count());

        ChatChannel::query()->delete();
        $oldest = ChatChannel::factory()->general()->create();
        ChatChannel::factory()->create();
        $this->assertTrue($chat->room()->is($oldest));

        $room = ChatChannel::factory()->room()->create();
        $this->assertTrue($chat->room()->is($room));
    }

    #[Test]
    public function there_is_one_room_at_community_chat_and_every_old_address_redirects_there(): void
    {
        ChatChannel::factory()->room()->create();

        $this->actingAs($this->member)->get(route('community.chat'))->assertOk()
            ->assertSee('<h1 id="room-heading" class="chat-room__title">'.ChatSettings::ROOM_NAME.'</h1>', false)
            ->assertDontSee('data-channels', false);

        foreach (['general', 'soil-science', 'scientific-community'] as $old) {
            $this->get('/community/chat/'.$old)->assertStatus(301)->assertRedirect(route('community.chat'));
        }
    }

    #[Test]
    public function the_room_never_sends_html_back_on_a_send_or_a_poll(): void
    {
        $component = Livewire::actingAs($this->member)->test(Room::class);

        $component->call('send', 'Hello', (string) Str::uuid(), 0, null);
        $this->assertArrayNotHasKey('html', $component->effects);

        $component->call('sync', 0, null);
        $this->assertArrayNotHasKey('html', $component->effects);
    }

    #[Test]
    public function members_see_everyone_without_admin_actions(): void
    {
        User::factory()->member()->create(['name' => 'Yusuf Abdallah']);

        $members = Livewire::actingAs($this->member)->test(Room::class)->get('initial')['members'];

        $this->assertSame(['Amina Hassan', 'Yusuf Abdallah'], array_column($members, 'name'));
        $this->assertArrayNotHasKey('profile_url', $members[0]);
        $this->assertArrayNotHasKey('can_suspend', $members[0]);
    }

    #[Test]
    public function an_administrator_gets_a_profile_link_and_can_suspend_from_the_members_tab(): void
    {
        $admin = User::factory()->admin()->create();
        $component = Livewire::actingAs($admin)->test(Room::class);
        $amina = collect($component->get('initial')['members'])->firstWhere('id', $this->member->id);

        $this->assertSame(route(AdminNavigation::routeFor(AdminNavigation::USERS), ['search' => $this->member->email]), $amina['profile_url']);
        $this->assertTrue($amina['can_suspend']);

        $component->call('suspendMember', $this->member->id)
            ->assertReturned(fn (array $result): bool => $result['ok'] === true
                && $result['notice'] === 'Amina Hassan has been suspended.'
                && ! in_array($this->member->id, array_column($result['members'], 'id'), true));

        $this->assertSame(CommunityStatus::Suspended, $this->member->fresh()->community_status);

        // The suspended member can no longer open the room.
        Livewire::actingAs($this->member->fresh())->test(Room::class)->assertForbidden();
    }

    #[Test]
    public function a_member_cannot_suspend_anyone(): void
    {
        $other = User::factory()->member()->create();

        Livewire::actingAs($this->member)->test(Room::class)
            ->call('suspendMember', $other->id)
            ->assertReturned(fn (array $result): bool => $result['ok'] === false && $result['error'] === Room::ERROR_ACCESS);

        $this->assertSame(CommunityStatus::Approved, $other->fresh()->community_status);
    }

    #[Test]
    public function the_client_config_carries_the_tabs_the_search_threshold_and_the_composer_cap(): void
    {
        $config = ChatSettings::clientConfig();

        $this->assertSame(['about', 'pinned', 'members'], $config['tabs']);
        $this->assertSame(20, $config['memberSearchThreshold']);
        $this->assertSame(40, $config['composerMaxViewportPercent']);
        $this->assertSame('Actions for :name', $config['copy']['memberMenu']);
    }

    #[Test]
    public function the_members_tab_marks_you_and_who_is_online(): void
    {
        $html = $this->actingAs($this->member)->get(route('community.chat'))->assertOk()->getContent();

        $this->assertStringContainsString('data-you>'.ChatCopy::STATUS_YOU.'</span>', $html);
        $this->assertStringContainsString(':class="{ \'is-online\': isOnline(member) }"', $html);
        $this->assertStringContainsString('aria-haspopup="true"', $html);
    }

    #[Test]
    public function administrators_set_the_upload_switch_and_limits_in_site_settings(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(SiteSettings::class)
            ->assertSet('form.'.SettingKeys::CHAT_UPLOADS_ENABLED, true)
            ->assertSee('Community chat uploads')
            ->set('form.'.SettingKeys::CHAT_UPLOADS_ENABLED, false)
            ->set('form.'.SettingKeys::CHAT_IMAGE_MAX_MB, '200')
            ->call('save')
            ->assertHasErrors('form.'.SettingKeys::CHAT_IMAGE_MAX_MB)
            ->set('form.'.SettingKeys::CHAT_IMAGE_MAX_MB, '6')
            ->set('form.'.SettingKeys::CHAT_DOCUMENT_MAX_MB, '12')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('form.'.SettingKeys::CHAT_UPLOADS_ENABLED, false);

        $settings = $this->app->make(SettingService::class);
        $this->assertSame(['0', '6', '12'], [$settings->get(SettingKeys::CHAT_UPLOADS_ENABLED), $settings->get(SettingKeys::CHAT_IMAGE_MAX_MB), $settings->get(SettingKeys::CHAT_DOCUMENT_MAX_MB)]);
    }
}
