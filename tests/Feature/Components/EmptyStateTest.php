<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmptyStateTest extends TestCase
{
    #[Test]
    public function it_renders_a_short_brass_rule_and_a_quiet_italic_line(): void
    {
        $html = Blade::render('<x-empty-state message="No publications yet." />');

        $this->assertMatchesRegularExpression(
            '/<hr class="rule-brass-short" data-rule>\s*<p class="font-display text-2xl font-light italic text-ash">No publications yet\.<\/p>/',
            $html,
        );
    }

    #[Test]
    public function tone_ivory_uses_champagne_text_for_evergreen_surfaces(): void
    {
        $this->assertStringContainsString('italic text-champagne', Blade::render('<x-empty-state message="Nothing here." tone="ivory" />'));
        $this->assertStringContainsString('class="empty-state flex flex-col items-start gap-5 py-12 mt-6"', Blade::render('<x-empty-state message="x" class="mt-6" />'));
    }
}
