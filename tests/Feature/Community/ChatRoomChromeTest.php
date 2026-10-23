<?php

namespace Tests\Feature\Community;

use App\Constants\AdminNavigation;
use App\Constants\BrandAudit;
use App\Constants\ChatCopy;
use App\Constants\ChatSettings;
use App\Constants\CommunityStatus;
use App\Livewire\Community\Chat\Room;
use App\Models\ChatChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The chat's chrome around the conversation: the header with one "Community"
 * label and the member menu, the rail's Members link and descriptions, the
 * icon toolbar, the composer ("Aa", icon send, the hint) and "Online now",
 * plus right-to-left messages and the poll's presence data.
 */
class ChatRoomChromeTest extends TestCase
{
    use RefreshDatabase;

    private ChatChannel $channel;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = ChatChannel::factory()->general()->create();
        $this->member = User::factory()->member()->create(['name' => 'Amina Hassan', 'affiliation' => 'University of Khartoum']);
    }

    private function page(): string
    {
        return $this->actingAs($this->member)->get(route('community.chat'))->assertOk()->getContent();
    }

    private function header(string $html): string
    {
        preg_match('~<header class="chat-header.*?</header>~s', $html, $match);

        return $match[0] ?? '';
    }

    #[Test]
    public function the_header_has_one_community_label_the_theme_switch_and_the_member_menu(): void
    {
        $header = $this->header($this->page());

        $this->assertNotSame('', $header);
        $this->assertSame(1, substr_count($header, '>'.ChatCopy::HEADER_LABEL.'<'));
        $this->assertStringNotContainsString(ChatCopy::TITLE, $header);
        $this->assertStringContainsString('id="theme-switch-chat"', $header);
        $this->assertStringContainsString('data-member-menu', $header);
        $this->assertStringContainsString('<span class="chat-avatar" aria-hidden="true">AH</span>', $header);
        $this->assertStringContainsString('University of Khartoum', $header);
        $this->assertStringContainsString(route(AdminNavigation::ACCOUNT_ROUTE), $header);
        $this->assertStringContainsString('action="'.route('logout').'"', $header);
        $this->assertMatchesRegularExpression('~</svg>\s*'.ChatCopy::MENU_SIGN_OUT.'</button>~', $header);
        // Sign out lives only in the menu now: no uppercase nav-link buttons in the bar.
        $this->assertStringNotContainsString('class="nav-link t-label">Sign out', $header);
    }

    #[Test]
    public function there_is_no_channel_rail_and_the_header_opens_the_members_tab(): void
    {
        $html = $this->page();

        $this->assertStringNotContainsString('data-chat-rail', $html);
        $this->assertStringNotContainsString('data-channels', $html);
        $this->assertStringContainsString('<h1 id="room-heading" class="chat-room__title">'.ChatSettings::ROOM_NAME.'</h1>', $html);
        $this->assertStringContainsString('x-on:click="showMembers()" aria-controls="chat-panel"', $html);
        $this->assertStringContainsString(ChatCopy::MEMBERS_LINK, $html);
    }

    #[Test]
    public function message_actions_are_icon_buttons_with_names_and_tooltips(): void
    {
        $html = $this->page();

        foreach ([ChatCopy::ACTION_REPLY, ChatCopy::ACTION_COPY_LINK, ChatCopy::ACTION_EDIT, ChatCopy::ACTION_DELETE, ChatCopy::ACTION_REPORT, ChatCopy::ACTION_PIN] as $action) {
            $this->assertStringContainsString('aria-label="'.$action.'" data-tip="'.$action.'"', $html);
        }

        $this->assertStringContainsString('role="toolbar" aria-label="Message actions" x-show="toolbarShown(m)"', $html);
        $this->assertStringContainsString('data-avatar', $html);
    }

    #[Test]
    public function every_message_body_reads_its_own_direction(): void
    {
        $this->assertStringContainsString('class="chat-message__body" dir="auto"', $this->page());
    }

    #[Test]
    public function the_composer_has_the_formatting_help_an_icon_send_and_the_retiring_hint(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('placeholder="'.ChatCopy::COMPOSER_PLACEHOLDER.'"', $html);
        $this->assertStringContainsString('aria-label="'.ChatCopy::FORMATTING_TOGGLE.'"', $html);
        $this->assertStringContainsString('<code class="chat-code">**bold**</code>', $html);
        $this->assertStringContainsString('aria-label="'.ChatCopy::SEND.'" data-tip="'.ChatCopy::SEND.'" data-send', $html);
        $this->assertStringContainsString('x-show="hintShown" data-composer-hint>'.ChatCopy::COMPOSER_HINT, $html);
        $this->assertStringContainsString('x-text="countText"', $html);
        $this->assertStringNotContainsString(' / <span x-text="config.bodyMaxLength">', $html);
    }

    #[Test]
    public function the_panel_has_about_pinned_and_members_tabs_with_the_full_member_list(): void
    {
        $html = $this->page();

        foreach ([ChatSettings::TAB_ABOUT => ChatCopy::TAB_ABOUT, ChatSettings::TAB_PINNED => ChatCopy::TAB_PINNED, ChatSettings::TAB_MEMBERS => ChatCopy::TAB_MEMBERS] as $tab => $label) {
            $this->assertMatchesRegularExpression('~role="tab" class="chat-tab" id="chat-tab-'.$tab.'" aria-controls="chat-tabpanel-'.$tab.'"[^>]*>\s*'.$label.'~', $html);
            $this->assertStringContainsString('role="tabpanel" id="chat-tabpanel-'.$tab.'" aria-labelledby="chat-tab-'.$tab.'"', $html);
        }

        // Every member is listed (no folded "All members"), with "online" and the member's own "You".
        $this->assertStringContainsString('x-for="member in filteredMembers"', $html);
        $this->assertStringContainsString('data-online>'.ChatCopy::MEMBER_ONLINE.'</span>', $html);
        $this->assertStringContainsString('data-you>'.ChatCopy::STATUS_YOU.'</span>', $html);
        $this->assertStringNotContainsString('data-members-all', $html);
        $this->assertStringContainsString('x-show="members.length > config.memberSearchThreshold" data-member-search', $html);
    }

    #[Test]
    public function headings_in_the_panel_are_not_focus_targets(): void
    {
        $html = $this->page();

        $this->assertDoesNotMatchRegularExpression('~<h[1-6][^>]*tabindex=~', $html);
    }

    #[Test]
    public function the_client_config_carries_the_hint_threshold_and_count_copy(): void
    {
        $config = ChatSettings::clientConfig();

        $this->assertSame(3, $config['hintHiddenAfterSends']);
        $this->assertSame(ChatSettings::SENT_COUNT_STORAGE_KEY, $config['sentCountKey']);
        $this->assertSame(1800, ChatSettings::BODY_MAX_LENGTH - $config['countWarningRemaining']);
        $this->assertSame(':count characters left', $config['copy']['countLeft']);
    }

    #[Test]
    public function the_poll_returns_who_is_online_including_the_member_polling(): void
    {
        $component = Livewire::actingAs($this->member)->test(Room::class);

        $component->call('sync', 0, null)->assertReturned(fn (array $result): bool => $result['ok'] === true && $result['online'] === [$this->member->id]);
    }

    #[Test]
    public function a_suspended_member_gets_no_presence_from_the_poll(): void
    {
        $component = Livewire::actingAs($this->member)->test(Room::class);
        $this->member->forceFill(['community_status' => CommunityStatus::Suspended])->save();

        $component->call('sync', 0, null)->assertReturned(fn (array $result): bool => $result['ok'] === false && ! isset($result['online']));
    }

    #[Test]
    public function the_arrival_rise_is_a_permitted_brand_motion(): void
    {
        $this->assertContains('chat-arrive', BrandAudit::permittedKeyframes());
    }
}
