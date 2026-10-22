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

    /* ---------------------------------------------------------- light formatting */

    #[Test]
    public function bold_italic_and_code_markers_become_tags(): void
    {
        $this->assertSame('<strong>bold</strong> and <em>italic</em> and <em>under</em>', ChatText::toHtml('**bold** and *italic* and _under_'));
        $this->assertSame('a <code class="chat-code">x &lt;- y &amp; z</code> b', ChatText::toHtml('a `x <- y & z` b'));
        $this->assertSame('snake_case_name 2*3*4 a * b * c', ChatText::toHtml('snake_case_name 2*3*4 a * b * c'));
        $this->assertSame('<code class="chat-code">**not bold**</code>', ChatText::toHtml('`**not bold**`'));
    }

    #[Test]
    public function bulleted_and_numbered_lines_become_lists_and_other_lines_keep_their_breaks(): void
    {
        $this->assertSame('<ul><li>one</li><li>two <strong>b</strong></li></ul>after', ChatText::toHtml("- one\n* two **b**\nafter"));
        $this->assertSame('intro<ol><li>first</li><li>second</li></ol>', ChatText::toHtml("intro\n1. first\n2) second"));
        $this->assertSame("line one\nline two\n\nline four", ChatText::toHtml("line one\nline two\n\nline four"));
        $this->assertSame("<strong>b</strong>\nnext", ChatText::toHtml("**b**\nnext"));
    }

    #[Test]
    public function markdown_links_are_rendered_only_for_http_targets(): void
    {
        $this->assertSame(
            '<a href="https://uofk.edu/x?a=1&amp;b=2" rel="nofollow noopener noreferrer" target="_blank">Khartoum</a>',
            ChatText::toHtml('[Khartoum](https://uofk.edu/x?a=1&b=2)'),
        );
        $this->assertSame('[bad](javascript:alert(1))', ChatText::toHtml('[bad](javascript:alert(1))'));
        $this->assertSame('[x](ftp://x.test)', ChatText::toHtml('[x](ftp://x.test)'));
        $this->assertStringNotContainsString('<a href="https://x.test"><a', ChatText::toHtml('[https://x.test](https://x.test)'));
    }

    #[Test]
    public function maths_is_kept_as_tex_inside_a_span_for_katex(): void
    {
        $html = ChatText::toHtml('inline $E = mc^2$ and $$\\int_0^1 x\\,dx$$ costs $5 and $10, escaped \\$7');

        $this->assertStringContainsString('<span class="chat-math" data-math="inline">E = mc^2</span>', $html);
        $this->assertStringContainsString('<span class="chat-math" data-math="display">\\int_0^1 x\\,dx</span>', $html);
        $this->assertStringContainsString('costs $5 and $10, escaped $7', $html);
        $this->assertSame('<span class="chat-math" data-math="inline">a_1 &lt; b</span>', ChatText::toHtml('$a_1 < b$'));
    }

    #[Test]
    public function markup_hidden_inside_markers_links_and_maths_is_still_escaped(): void
    {
        $cases = [
            '**<b onmouseover="x()">bold</b>**',
            '$<img src=x onerror=alert(1)>$',
            '$$<script>alert(1)</script>$$',
            '`<script>alert(1)</script>`',
            '[<script>alert(1)</script>](https://x.test)',
            '[x](https://x.test"onclick="alert(1))',
            '- <svg onload=alert(1)>',
            '*<iframe src="https://evil.test"></iframe>*',
            '<a href="javascript:alert(1)">x</a>',
            "[x](https://x.test/\u{2028}javascript:)",
        ];

        foreach ($cases as $case) {
            $html = ChatText::toHtml($case);

            $this->assertStringNotContainsString('<script', $html, $case);
            $this->assertStringNotContainsString('<img', $html, $case);
            $this->assertStringNotContainsString('<svg', $html, $case);
            $this->assertStringNotContainsString('<iframe', $html, $case);
            $this->assertStringNotContainsString('href="javascript:', $html, $case);
            $this->assertDoesNotMatchRegularExpression('/<[a-z]+[^>]*\s(on\w+|style)=/i', $html, $case);
        }

        $this->assertSame('<strong>&lt;b onmouseover=&quot;x()&quot;&gt;bold&lt;/b&gt;</strong>', ChatText::toHtml('**<b onmouseover="x()">bold</b>**'));
    }

    #[Test]
    public function excerpts_are_one_plain_line_without_markers(): void
    {
        $this->assertSame('bold and italic, code, Khartoum, one two', ChatText::excerpt("**bold** and *italic*, `code`, [Khartoum](https://x.test),\n- one\n- two"));
        $this->assertSame(str_repeat('a', 10).'…', ChatText::excerpt(str_repeat('a', 30), 10));
    }
}
