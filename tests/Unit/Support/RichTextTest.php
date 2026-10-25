<?php

namespace Tests\Unit\Support;

use App\Support\RichText;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RichTextTest extends TestCase
{
    #[Test]
    public function it_returns_an_empty_string_for_nothing(): void
    {
        $this->assertSame('', RichText::sanitise(null));
        $this->assertSame('', RichText::sanitise('   '));
    }

    #[Test]
    public function it_keeps_the_allowed_block_and_inline_elements(): void
    {
        $html = '<p>One <em>two</em> <strong>three</strong></p><h2>Heading</h2><h3>Sub</h3><blockquote><p>Quote</p></blockquote><ul><li>a</li></ul><ol><li>b</li></ol><br>';

        $this->assertSame($html, RichText::sanitise($html));
    }

    #[Test]
    public function it_removes_scripts_unknown_elements_and_every_attribute(): void
    {
        $html = '<p class="x" onclick="alert(1)">Hi</p><script>alert(1)</script><img src="x"><div style="color:red">Boxed</div><h2 id="a">T</h2>';

        $this->assertSame('<p>Hi</p>Boxed<h2>T</h2>', RichText::sanitise($html));
    }

    #[Test]
    public function it_keeps_only_safe_hrefs_on_links(): void
    {
        $this->assertSame(
            '<a href="https://example.com/a?b=1&amp;c=2" rel="noopener">Site</a>',
            RichText::sanitise('<a href="https://example.com/a?b=1&amp;c=2" target="_blank" onclick="x()">Site</a>'),
        );
        $this->assertSame('<a href="mailto:x@example.com" rel="noopener">Mail</a>', RichText::sanitise("<a href='mailto:x@example.com'>Mail</a>"));
        $this->assertSame('<a>Bad</a>', RichText::sanitise('<a href="javascript:alert(1)">Bad</a>'));
        $this->assertSame('<a>Rel</a>', RichText::sanitise('<a href="/relative">Rel</a>'));
    }

    #[Test]
    public function it_reports_whether_a_pull_quote_is_present(): void
    {
        $this->assertTrue(RichText::hasBlockquote(RichText::sanitise('<p>a</p><blockquote class="q">b</blockquote>')));
        $this->assertFalse(RichText::hasBlockquote(RichText::sanitise('<p>a</p>')));
    }
}
