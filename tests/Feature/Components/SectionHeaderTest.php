<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionHeaderTest extends TestCase
{
    #[Test]
    public function it_renders_label_drawing_rule_and_heading_in_order(): void
    {
        $html = Blade::render('<x-section-header label="Chapter One — Early Years" title="A childhood among books" />');

        $this->assertMatchesRegularExpression(
            '/<p class="t-label text-bronze" data-rise>Chapter One — Early Years<\/p>\s*<hr class="rule-brass mt-4" data-rule>\s*<h2 class="t-heading mt-8" data-rise>A childhood among books<\/h2>/',
            $html,
        );
    }

    #[Test]
    public function the_label_turns_brass_on_evergreen_and_is_optional(): void
    {
        $this->assertStringContainsString('t-label text-brass', Blade::render('<x-section-header label="Awards" title="Awards" tone="ivory" />'));
        $this->assertStringNotContainsString('t-label', Blade::render('<x-section-header title="Awards" />'));
    }

    #[Test]
    public function heading_level_id_and_intro_slot_are_supported(): void
    {
        $html = Blade::render('<x-section-header title="Publications" level="1" id="page-title">Books and papers.</x-section-header>');

        $this->assertStringContainsString('<h1 id="page-title" class="t-heading mt-8" data-rise>Publications</h1>', $html);
        $this->assertMatchesRegularExpression('/<div class="t-body mt-6 max-w-2xl text-ash" data-rise>\s*Books and papers\.\s*<\/div>/', $html);
        $this->assertStringContainsString('<h2 ', Blade::render('<x-section-header title="x" level="9" />'), 'Invalid levels fall back to h2');
    }
}
