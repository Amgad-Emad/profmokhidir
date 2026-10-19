<?php

namespace Tests\Unit\Support;

use App\Support\ChatText;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ChatTextTest extends TestCase
{
    #[Test]
    public function everything_is_escaped(): void
    {
        $html = ChatText::toHtml('<script>alert("x")</script> & <b>bold</b>');

        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &amp; &lt;b&gt;bold&lt;/b&gt;', $html);
    }

    #[Test]
    public function urls_are_auto_linked_with_nofollow_noopener(): void
    {
        $html = ChatText::toHtml('See https://example.org/path?a=1&b=2 for details.');

        $this->assertSame(
            'See <a href="https://example.org/path?a=1&amp;b=2" rel="nofollow noopener noreferrer" target="_blank">https://example.org/path?a=1&amp;b=2</a> for details.',
            $html,
        );
    }

    #[Test]
    public function trailing_punctuation_stays_outside_the_link_and_wikipedia_parentheses_stay_inside(): void
    {
        $html = ChatText::toHtml('http://a.test, then http://b.test!');
        $this->assertStringContainsString('>http://a.test</a>, then <a href="http://b.test"', $html);
        $this->assertStringEndsWith('>http://b.test</a>!', $html);
        $this->assertStringContainsString('href="https://en.wikipedia.org/wiki/Sorghum_(plant)"', ChatText::toHtml('(see https://en.wikipedia.org/wiki/Sorghum_(plant))'));
        $this->assertStringEndsWith('</a>)', ChatText::toHtml('(see https://en.wikipedia.org/wiki/Sorghum_(plant))'));
    }

    #[Test]
    public function javascript_and_other_schemes_are_not_linked(): void
    {
        $html = ChatText::toHtml('javascript:alert(1) ftp://x.test mailto:a@b.test');

        $this->assertStringNotContainsString('<a ', $html);
    }

    #[Test]
    public function markup_inside_a_url_cannot_break_out_of_the_attribute(): void
    {
        $html = ChatText::toHtml('https://example.org/"><img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('href="https://example.org/&quot;&gt;&lt;img', $html);
    }

    #[Test]
    public function normalise_trims_and_folds_line_endings(): void
    {
        $this->assertSame("a\nb\nc", ChatText::normalise("  a\r\nb\rc \n"));
        $this->assertSame('', ChatText::normalise("  \n\t "));
    }

    #[Test]
    public function arabic_and_emoji_survive(): void
    {
        $this->assertSame('مرحباً 🌱', ChatText::toHtml('مرحباً 🌱'));
    }
}
