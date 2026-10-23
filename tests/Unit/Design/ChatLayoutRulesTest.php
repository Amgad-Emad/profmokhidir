<?php

namespace Tests\Unit\Design;

use App\Constants\ChatSettings;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The conversation fills the centre column up to "About this room" (the whole
 * width when the panel is closed): the message list, every message, the
 * attachment grids and the composer. Only message text keeps a measure.
 */
class ChatLayoutRulesTest extends TestCase
{
    private function css(): string
    {
        return (string) preg_replace('/\/\*.*?\*\//s', '', (string) file_get_contents(resource_path('css/chat.css')));
    }

    /** The body of the first rule for an exact selector. */
    private function rule(string $selector): string
    {
        preg_match('/(?:^|[}\s])'.preg_quote($selector, '/').'\s*\{([^{}]*)\}/', $this->css(), $match);

        return $match[1] ?? '';
    }

    #[Test]
    public function the_side_padding_is_one_gutter_token_of_24_and_then_40_pixels(): void
    {
        $css = $this->css();

        $this->assertStringContainsString('--chat-gutter: '.(ChatSettings::GUTTER_PX / 16).'rem;', $css);
        $this->assertMatchesRegularExpression('/@media \(min-width: 1024px\) \{\s*:root \{\s*--chat-gutter: '.preg_quote((string) (ChatSettings::GUTTER_DESKTOP_PX / 16), '/').'rem;/', $css);

        foreach (['.chat-room__header', '.chat-scroller', '.chat-composer', '.chat-search', '.chat-banner'] as $selector) {
            $this->assertStringContainsString('var(--chat-gutter)', $this->rule($selector), $selector);
        }
    }

    #[Test]
    public function nothing_in_the_conversation_stops_short_of_the_column(): void
    {
        foreach (['.chat-log', '.chat-composer__inner', '.chat-attachments', '.chat-gallery', '.chat-files', '.chat-tray'] as $selector) {
            $this->assertStringNotContainsString('max-width', $this->rule($selector), $selector);
        }

        $this->assertStringNotContainsString('68ch', $this->css());
        $this->assertStringNotContainsString('.chat-gallery.is-1 {', $this->css(), 'a single image is not boxed in');
    }

    #[Test]
    public function only_message_text_keeps_a_measure(): void
    {
        $this->assertStringContainsString('max-width: '.ChatSettings::MESSAGE_MEASURE_CH.'ch;', $this->rule('.chat-message__body'));
    }

    #[Test]
    public function the_grids_follow_the_column_not_the_window(): void
    {
        $css = $this->css();

        $this->assertStringContainsString('container-type: inline-size;', $this->rule('.chat-attachments'));
        $this->assertMatchesRegularExpression('/@container \(min-width: 64rem\) \{\s*\.chat-files:not\(\.is-1, \.is-2, \.is-4\) \{\s*grid-template-columns: repeat\(3, minmax\(0, 1fr\)\);/', $css);
        $this->assertMatchesRegularExpression('/@container \(min-width: 80rem\) \{\s*\.chat-gallery \{\s*grid-template-columns: repeat\(5, minmax\(0, 1fr\)\);/', $css);
        $this->assertStringContainsString('animation: skeleton-breathe', $this->rule('.chat-gallery__item:not(.is-loaded)'));
    }
}
