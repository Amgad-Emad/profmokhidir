<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmptyStateTest extends TestCase
{
    #[Test]
    public function it_renders_a_short_brass_rule_and_the_line_as_an_epigraph(): void
    {
        $html = Blade::render('<x-empty-state message="No publications yet." />');

        $this->assertMatchesRegularExpression(
            '/<figure class="empty-state flex flex-col items-start gap-5 py-12">\s*<hr class="rule-brass-short" data-rule>\s*<blockquote class="empty-state__quote">\s*<p class="font-display text-2xl font-light italic text-ink-muted">No publications yet\.<\/p>\s*<\/blockquote>\s*<\/figure>/',
            $html,
        );
        $this->assertStringNotContainsString('figcaption', $html, 'No attribution unless one is given');
    }

    #[Test]
    public function an_attribution_is_set_in_small_caps_after_a_dash(): void
    {
        $html = Blade::render('<x-empty-state message="Nothing answers." attribution="The archivist" />');

        $this->assertMatchesRegularExpression(
            '/<figcaption class="empty-state__attribution smallcaps text-ink-muted"><span aria-hidden="true">— <\/span>The archivist<\/figcaption>/u',
            $html,
        );
    }

    #[Test]
    public function tone_ivory_uses_champagne_text_for_evergreen_surfaces(): void
    {
        $html = Blade::render('<x-empty-state message="Nothing here." attribution="A reader" tone="ivory" />');

        $this->assertStringContainsString('italic text-ink', $html);
        $this->assertStringContainsString('smallcaps text-ink', $html);
        $this->assertStringContainsString('class="empty-state flex flex-col items-start gap-5 py-12 mt-6"', Blade::render('<x-empty-state message="x" class="mt-6" />'));
    }
}
