<?php

namespace Tests\Feature\Community;

use App\Livewire\Community\Chat\Room;
use App\Models\ChatChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression tests for the four problems the owner reported in the chat. They
 * use literal values (no constants added by the fix) so that the same file
 * runs against the code before the fix, where every test here fails.
 *
 *  1. Channels: old channel addresses must move permanently to the one room.
 *  2. Typing stopped after the first line: every poll re-rendered the component
 *     and the morph stripped the composer's inline height (and could touch its
 *     attributes) while the member typed.
 *  3. "Members" left a white area: absolutely positioned (visually hidden) text
 *     inside the panel escaped its scroll box and stretched the document, which
 *     scrolled under body { overflow: hidden }; the list itself was folded away,
 *     and the "Online now" heading took focus with a heavy ring.
 */
class ChatFixesRegressionTest extends TestCase
{
    use RefreshDatabase;

    private ChatChannel $channel;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = ChatChannel::factory()->general()->create();
        $this->member = User::factory()->member()->create(['name' => 'Amina Hassan']);
    }

    private function page(): string
    {
        return $this->actingAs($this->member)->followingRedirects()->get('/community/chat')->assertOk()->getContent();
    }

    #[Test]
    public function an_old_channel_address_moves_permanently_to_the_one_room(): void
    {
        $this->actingAs($this->member)->get('/community/chat/general?message=12')
            ->assertStatus(301)
            ->assertRedirect('/community/chat?message=12');
    }

    #[Test]
    public function a_poll_never_re_renders_the_room_so_the_composer_is_never_morphed(): void
    {
        $component = Livewire::actingAs($this->member)->test(Room::class, ['channel' => $this->channel]);

        $component->call('sync', 0, null);

        $this->assertArrayNotHasKey('html', $component->effects, 'A poll must not send HTML back: morphing it resets the composer while the member types.');
    }

    #[Test]
    public function the_composer_is_outside_livewire_morphing_and_has_no_client_side_length_cap(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('wire:ignore data-chat-shell', $html);
        $this->assertMatchesRegularExpression('~<textarea[^>]*id="chat-draft"[^>]*>~', $html);
        preg_match('~<textarea[^>]*id="chat-draft"[^>]*>~', $html, $textarea);
        $this->assertStringNotContainsString('maxlength', $textarea[0], 'A maxlength silently cuts a long paste; the limit is enforced on the server.');
        $this->assertStringContainsString('x-model="draft"', $textarea[0]);
    }

    #[Test]
    public function the_composer_wraps_long_words_and_grows_to_forty_percent_of_the_viewport(): void
    {
        $rule = $this->cssRule('textarea.chat-composer__input');

        $this->assertStringContainsString('overflow-wrap: anywhere', $rule);
        $this->assertStringContainsString('max-height: 40dvh', $rule);
        $this->assertStringContainsString('overflow-y: auto', $rule);
    }

    #[Test]
    public function the_room_is_a_fixed_height_shell_that_clips_its_contents(): void
    {
        $this->assertStringContainsString('overflow: clip', $this->cssRule('.chat-page'));
        $this->assertStringContainsString('height: calc(100dvh - var(--chat-header))', $this->cssRule('.chat-page'));
        $this->assertStringContainsString('position: relative', $this->cssRule(".chat-room,\n    .chat-panel"));
    }

    #[Test]
    public function the_members_list_is_not_folded_and_no_heading_takes_focus(): void
    {
        $html = $this->page();

        $this->assertStringNotContainsString('data-members-all', $html);
        $this->assertDoesNotMatchRegularExpression('~<h[1-6][^>]*tabindex=~', $html);
    }

    /**
     * The first rule in chat.css for exactly this selector.
     */
    private function cssRule(string $selector): string
    {
        $css = (string) file_get_contents(resource_path('css/chat.css'));
        $start = strpos($css, "    {$selector} {");
        $this->assertNotFalse($start, "chat.css has no rule for {$selector}");

        return substr($css, $start, strpos($css, '}', $start) - $start);
    }
}
