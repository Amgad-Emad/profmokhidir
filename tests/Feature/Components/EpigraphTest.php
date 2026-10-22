<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EpigraphTest extends TestCase
{
    #[Test]
    public function it_renders_the_quote_and_a_small_caps_attribution(): void
    {
        $html = Blade::render('<x-epigraph attribution="Prof. M. O. Khidir">Knowledge is the light we pass on.</x-epigraph>');

        $this->assertStringContainsString('<figure class="epigraph text-ink-muted">', $html);
        $this->assertStringContainsString('<blockquote class="epigraph__quote"><p>Knowledge is the light we pass on.</p></blockquote>', $html);
        $this->assertStringContainsString("<figcaption class=\"epigraph__attribution\"><span aria-hidden=\"true\">— </span><span class=\"smallcaps\">Prof.\u{00A0}M.\u{00A0}O.\u{00A0}Khidir</span></figcaption>", $html);
    }

    #[Test]
    public function the_attribution_is_optional_and_the_ivory_tone_uses_champagne(): void
    {
        $html = Blade::render('<x-epigraph tone="ivory">A quote.</x-epigraph>');

        $this->assertStringContainsString('class="epigraph text-ink"', $html);
        $this->assertStringNotContainsString('figcaption', $html);
    }
}
