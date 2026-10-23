<?php

namespace Tests\Unit\Constants;

use App\Constants\ChatCopy;
use App\Constants\ChatFormat;
use App\Constants\ChatSettings;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatFormatTest extends TestCase
{
    #[Test]
    public function the_allowlist_names_only_the_light_formatting_tags(): void
    {
        $this->assertSame(['strong', 'em', 'code', 'a', 'ul', 'ol', 'li', 'br', 'span'], array_keys(ChatFormat::allowedTags()));
        $this->assertSame(['href', 'rel', 'target'], ChatFormat::allowedTags()['a']);
        $this->assertSame(['class', ChatFormat::MATH_ATTRIBUTE], ChatFormat::allowedTags()['span']);
        $this->assertSame(['http', 'https'], ChatFormat::LINK_SCHEMES);
        $this->assertSame('nofollow noopener noreferrer', ChatFormat::LINK_ATTRIBUTES['rel']);
        $this->assertSame(['inline', 'display'], ChatFormat::mathValues());
        $this->assertSame([ChatFormat::CODE_CLASS, ChatFormat::MATH_CLASS], ChatFormat::allowedClasses());
    }

    #[Test]
    public function the_hints_cover_every_marker(): void
    {
        $markup = array_column(ChatFormat::hints(), 'markup');

        $this->assertCount(6, $markup);
        $this->assertStringContainsString(ChatFormat::BOLD, $markup[0]);
        $this->assertStringContainsString(ChatFormat::CODE, $markup[2]);
        $this->assertStringContainsString(ChatFormat::MATH_INLINE, $markup[5]);
    }

    #[Test]
    public function the_client_config_carries_the_copy_the_limits_and_the_katex_stylesheet(): void
    {
        $config = ChatSettings::clientConfig();

        $this->assertSame(ChatSettings::GROUP_WINDOW_SECONDS, $config['groupWindowSeconds']);
        $this->assertSame(300, ChatSettings::GROUP_WINDOW_SECONDS);
        $this->assertSame(ChatSettings::COUNT_WARNING_REMAINING, $config['countWarningRemaining']);
        $this->assertSame('message', $config['messageParam']);
        $this->assertSame(ChatSettings::DRAFT_STORAGE_PREFIX, $config['draftPrefix']);
        $this->assertSame(ChatFormat::MATH_ATTRIBUTE, $config['mathAttribute']);
        $this->assertStringEndsWith(ChatSettings::KATEX_STYLESHEET, $config['katexStylesheet']);
        $this->assertSame(ChatCopy::client(), $config['copy']);
        $this->assertSame(ChatCopy::SENDING, $config['copy']['sending']);
        $this->assertSame(ChatCopy::NOT_SENT, $config['copy']['notSent']);
        $this->assertSame(ChatCopy::RECONNECTING, $config['copy']['reconnecting']);
        $this->assertSame(ChatCopy::REPLY_REMOVED, $config['copy']['replyRemoved']);
    }

    #[Test]
    public function the_copy_reads_as_a_correspondence_room(): void
    {
        $this->assertSame('Write to the community…', ChatCopy::COMPOSER_PLACEHOLDER);
        $this->assertSame('Enter to send · Shift+Enter for a new line', ChatCopy::COMPOSER_HINT);
        $this->assertSame('New', ChatCopy::NEW_DIVIDER);
        $this->assertSame('Original message removed', ChatCopy::REPLY_REMOVED);
        $this->assertSame('About this room', ChatCopy::PANEL_TITLE);
        $this->assertSame('Not sent', ChatCopy::NOT_SENT);
        $this->assertSame('Retry', ChatCopy::RETRY);
        $this->assertSame('Reconnecting…', ChatCopy::RECONNECTING);
    }
}
