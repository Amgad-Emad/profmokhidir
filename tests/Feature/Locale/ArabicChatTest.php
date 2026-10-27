<?php

namespace Tests\Feature\Locale;

use App\Constants\ChatCopy;
use App\Constants\ChatSettings;
use App\Exceptions\ChatRateLimitException;
use App\Livewire\Community\Chat\Room;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The community chat follows the member's saved language: Arabic members get the room
 * right to left with Arabic chrome and script strings; messages keep their own direction.
 */
class ArabicChatTest extends TestCase
{
    use RefreshDatabase;

    private ChatChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = ChatChannel::factory()->general()->create();
    }

    private function postMessage(User $author, string $body): ChatMessage
    {
        return ChatMessage::factory()->for($this->channel, 'channel')->for($author, 'user')->create(['body' => $body]);
    }

    #[Test]
    public function an_arabic_member_sees_the_room_in_arabic_right_to_left(): void
    {
        $member = User::factory()->member()->arabic()->create(['name' => 'Amina Hassan']);
        $this->postMessage($member, 'مرحبا بالجميع, hello everyone');

        $this->actingAs($member)->get(route('community.chat'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl"', false)
            ->assertSee('window.i18n', false)
            // A script string (resources/js/chat.js) in the translations the layout prints.
            ->assertSee('"Yesterday":"أمس"', false)
            ->assertSee('المجتمع العلمي')
            ->assertSee('اكتب إلى المجتمع…')
            ->assertSee('عن هذه القاعة')
            ->assertSee('انتقل إلى المحتوى')
            ->assertDontSee('Write to the community…')
            // Messages are never translated: each body, quote and name finds its own direction.
            ->assertSee('class="chat-message__body" dir="auto"', false)
            ->assertSee('class="chat-reply__excerpt" :class="{ \'chat-reply__removed\': m.reply_to.deleted }" dir="auto"', false)
            ->assertSee('class="smallcaps chat-run__author" dir="auto"', false);
    }

    #[Test]
    public function the_client_copy_and_server_messages_come_in_arabic(): void
    {
        app()->setLocale('ar');
        $copy = ChatSettings::clientConfig()['copy'];

        $this->assertSame('نُسخ الرابط', $copy['linkCopied']);
        $this->assertSame('رسائل جديدة: :count', $copy['newMany']);
        $this->assertSame('حُذفت الرسالة', ChatSettings::clientConfig()['removedPlaceholder']);
        $this->assertStringContainsString('يُرجى الانتظار 12 ثانية', ChatRateLimitException::make(12)->getMessage());
    }

    #[Test]
    public function an_arabic_members_report_is_thanked_in_arabic(): void
    {
        $member = User::factory()->member()->arabic()->create();
        $other = User::factory()->member()->create();
        $message = $this->postMessage($other, 'A message to report');

        app()->setLocale('ar');

        Livewire::actingAs($member)->test(Room::class)
            ->call('report', $message->id, 'Off topic')
            ->assertReturned(fn (array $result): bool => $result['ok'] === true && $result['notice'] === __(ChatCopy::REPORT_THANKS) && $result['notice'] !== ChatCopy::REPORT_THANKS);
    }

    #[Test]
    public function an_english_member_still_sees_english(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->get(route('community.chat'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr"', false)
            ->assertDontSee('window.i18n', false)
            ->assertSee(ChatSettings::ROOM_NAME)
            ->assertSee(ChatCopy::COMPOSER_PLACEHOLDER)
            ->assertSee('class="chat-message__body" dir="auto"', false)
            ->assertDontSee('المجتمع العلمي');
    }
}
