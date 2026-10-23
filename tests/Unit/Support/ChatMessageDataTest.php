<?php

namespace Tests\Unit\Support;

use App\Constants\ChatCopy;
use App\Constants\ChatSettings;
use App\Models\ChatMessage;
use App\Models\User;
use App\Support\ChatMessageData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatMessageDataTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_live_message_carries_escaped_html_utc_times_and_the_viewers_permissions(): void
    {
        $author = User::factory()->member()->create(['name' => 'Amina', 'affiliation' => 'University of Khartoum']);
        $other = User::factory()->member()->create();
        $message = ChatMessage::factory()->for($author, 'user')->create(['body' => 'Hi <b>all</b> https://x.test']);
        $message->load('user');

        $mine = ChatMessageData::from($message, $author);
        $theirs = ChatMessageData::from($message, $other);
        $moderator = ChatMessageData::from($message, User::factory()->admin()->create(), true);

        $this->assertSame('Amina', $mine['author']);
        $this->assertSame('University of Khartoum', $mine['affiliation']);
        $this->assertSame('Hi <b>all</b> https://x.test', $mine['body']);
        $this->assertStringContainsString('Hi &lt;b&gt;all&lt;/b&gt; <a href="https://x.test"', $mine['html']);
        $this->assertStringEndsWith('Z', $mine['created_at']);
        $this->assertFalse($mine['deleted']);
        $this->assertTrue($mine['mine'] && $mine['can_edit'] && $mine['can_delete'] && ! $mine['can_report']);
        $this->assertTrue(! $theirs['mine'] && ! $theirs['can_edit'] && ! $theirs['can_delete'] && $theirs['can_report']);
        $this->assertTrue(! $moderator['can_edit'] && $moderator['can_delete']);
    }

    #[Test]
    public function a_deleted_message_exposes_no_body_and_no_actions(): void
    {
        $author = User::factory()->member()->create();
        $message = ChatMessage::factory()->for($author, 'user')->deleted()->create(['body' => 'secret']);
        $message->load('user');

        $data = ChatMessageData::from($message, $author);

        $this->assertTrue($data['deleted']);
        $this->assertSame('', $data['body']);
        $this->assertSame('', $data['html']);
        $this->assertNull($data['edited_at']);
        $this->assertFalse($data['can_edit'] || $data['can_delete'] || $data['can_report']);
        $this->assertSame(ChatSettings::REMOVED_PLACEHOLDER, ChatMessageData::removedPlaceholder());
    }

    #[Test]
    public function a_suspended_viewer_gets_no_actions_on_their_own_messages(): void
    {
        $author = User::factory()->suspendedMember()->create();
        $message = ChatMessage::factory()->for($author, 'user')->create();
        $message->load('user');

        $data = ChatMessageData::from($message, $author);

        $this->assertTrue($data['mine']);
        $this->assertFalse($data['can_edit'] || $data['can_delete']);
    }

    #[Test]
    public function a_message_of_files_only_is_quoted_and_pinned_as_attachments_and_carries_an_empty_file_list_by_default(): void
    {
        $files = ChatMessage::factory()->create(['body' => '']);
        $reply = ChatMessage::factory()->replyTo($files)->create();
        $viewer = User::factory()->member()->create();

        $this->assertSame(ChatCopy::ATTACHMENTS_LABEL, ChatMessageData::pin($files)['excerpt']);
        $this->assertSame(ChatCopy::ATTACHMENTS_LABEL, ChatMessageData::from($reply->load('replyTo.user'), $viewer)['reply_to']['excerpt']);
        $this->assertSame([], ChatMessageData::from($files, $viewer)['attachments']);
    }
}
