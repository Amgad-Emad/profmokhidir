<?php

namespace Tests\Unit\Support;

use App\Support\ChatSanitiser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The allowlist every rendered chat body passes through. Inputs here are the
 * HTML a bug or an attacker might produce; only App\Constants\ChatFormat's tags
 * and attributes may survive.
 */
class ChatSanitiserTest extends TestCase
{
    #[Test]
    public function allowed_formatting_passes_through_unchanged(): void
    {
        $html = '<strong>b</strong> <em>i</em> <code class="chat-code">c</code> <ul><li>x</li></ul><ol><li>y</li></ol> a<br>b';

        $this->assertSame($html, ChatSanitiser::clean($html));
    }

    #[Test]
    public function scripts_styles_and_event_handlers_are_removed(): void
    {
        $this->assertSame('before after', ChatSanitiser::clean('before <script>alert(1)</script>after'));
        $this->assertSame('x', ChatSanitiser::clean('<style>body{display:none}</style>x'));
        $this->assertSame('<strong>b</strong>', ChatSanitiser::clean('<strong onclick="alert(1)" style="color:red">b</strong>'));
        $this->assertSame('', ChatSanitiser::clean('<img src=x onerror=alert(1)>'));
        $this->assertSame('', ChatSanitiser::clean('<iframe src="https://evil.test"></iframe><object></object><embed>'));
        $this->assertSame('', ChatSanitiser::clean('<svg onload="alert(1)"><text>x</text></svg>'));
    }

    #[Test]
    public function unknown_tags_are_unwrapped_to_their_text(): void
    {
        $this->assertSame('a b c', ChatSanitiser::clean('<div>a <span>b</span> <h1>c</h1></div>'));
        $this->assertSame('quoted', ChatSanitiser::clean('<blockquote>quoted</blockquote>'));
    }

    #[Test]
    public function only_http_links_survive_and_always_carry_the_safe_attributes(): void
    {
        $this->assertSame(
            '<a href="https://x.test/?a=1&amp;b=2" rel="nofollow noopener noreferrer" target="_blank">x</a>',
            ChatSanitiser::clean('<a href="https://x.test/?a=1&amp;b=2" onclick="go()" target="_self" rel="opener">x</a>'),
        );
        $this->assertSame('x', ChatSanitiser::clean('<a href="javascript:alert(1)">x</a>'));
        $this->assertSame('x', ChatSanitiser::clean('<a href="JaVaScRiPt:alert(1)">x</a>'));
        $this->assertSame('x', ChatSanitiser::clean('<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>'));
        $this->assertSame('x', ChatSanitiser::clean('<a href="mailto:a@b.test">x</a>'));
        $this->assertSame('x', ChatSanitiser::clean('<a href="/relative">x</a>'));
        $this->assertSame('x', ChatSanitiser::clean('<a>x</a>'));
        $this->assertSame('x', ChatSanitiser::clean("<a href=\"https://x.test/\u{09}javascript:\">x</a>"));
    }

    #[Test]
    public function maths_spans_keep_only_their_tex_and_a_valid_mode(): void
    {
        $this->assertSame(
            '<span class="chat-math" data-math="inline">E = mc^2</span>',
            ChatSanitiser::clean('<span class="chat-math other" data-math="inline" title="x">E = mc^2</span>'),
        );
        $this->assertSame('tex', ChatSanitiser::clean('<span class="chat-math" data-math="evil">tex</span>'));
        $this->assertSame('tex', ChatSanitiser::clean('<span class="other" data-math="inline">tex</span>'));
        $this->assertSame('tex', ChatSanitiser::clean('<span data-math="inline">tex</span>'));
    }

    #[Test]
    public function text_and_attribute_values_are_re_escaped_on_the_way_out(): void
    {
        $this->assertSame('&lt;b&gt;not bold&lt;/b&gt; &amp; &quot;quoted&quot;', ChatSanitiser::clean('&lt;b&gt;not bold&lt;/b&gt; &amp; "quoted"'));
        $this->assertSame(
            '<a href="https://x.test/&quot;&gt;&lt;img" rel="nofollow noopener noreferrer" target="_blank">x</a>',
            ChatSanitiser::clean('<a href="https://x.test/&quot;&gt;&lt;img">x</a>'),
        );
    }

    #[Test]
    public function comments_cdata_and_broken_markup_cannot_smuggle_anything(): void
    {
        $this->assertSame('ab', ChatSanitiser::clean('a<!-- <script>x</script> -->b'));
        $this->assertSame('a &lt;b', ChatSanitiser::clean('a &lt;b'));
        $this->assertSame('<strong>unclosed</strong>', ChatSanitiser::clean('<strong>unclosed'));

        $nested = ChatSanitiser::clean('<a href="https://x.test"><a href="https://y.test">a</a>');
        $this->assertStringEndsWith('>a</a>', $nested);
        $this->assertDoesNotMatchRegularExpression('/<a [^>]*>[^<]*<a /', $nested, 'anchors never nest');
    }

    #[Test]
    public function arabic_emoji_and_newlines_round_trip(): void
    {
        $this->assertSame("مرحباً 🌱\nline two", ChatSanitiser::clean("مرحباً 🌱\nline two"));
        $this->assertSame('', ChatSanitiser::clean('   '));
    }

    #[Test]
    public function safe_url_accepts_only_absolute_http_and_https_urls(): void
    {
        $this->assertTrue(ChatSanitiser::safeUrl('https://x.test/path?q=1#f'));
        $this->assertTrue(ChatSanitiser::safeUrl('http://x.test'));
        $this->assertFalse(ChatSanitiser::safeUrl('ftp://x.test'));
        $this->assertFalse(ChatSanitiser::safeUrl('https://'));
        $this->assertFalse(ChatSanitiser::safeUrl('x.test'));
        $this->assertFalse(ChatSanitiser::safeUrl(''));
    }
}
