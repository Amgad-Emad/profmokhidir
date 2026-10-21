<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChapterOpeningTest extends TestCase
{
    #[Test]
    public function it_renders_numeral_label_drawing_rule_and_heading_in_order(): void
    {
        $html = Blade::render('<x-chapter-opening :number="3" label="Khartoum" title="The first lectures" />');

        $this->assertMatchesRegularExpression(
            '/<p class="chapter-opening__label text-bronze" data-rise>\s*<span class="t-numeral"><span class="sr-only">Chapter <\/span>III<\/span>\s*<span class="t-label">Khartoum<\/span>\s*<\/p>\s*<hr class="rule-brass chapter-opening__rule" data-rule>\s*<h2 class="t-heading chapter-opening__title" data-rise>The first lectures<\/h2>/',
            $html,
        );
    }

    #[Test]
    public function the_heading_is_typeset_and_supports_level_and_id(): void
    {
        $html = Blade::render('<x-chapter-opening title="Work for the FAO, 1975-1992" level="1" id="page-title" />');

        $this->assertStringContainsString('<h1 id="page-title" class="t-heading chapter-opening__title" data-rise>Work for the <span class="caps">FAO</span>, 1975–1992</h1>', $html);
        $this->assertStringNotContainsString('chapter-opening__label', $html, 'No numeral and no label: no label line');
        $this->assertStringContainsString('<h2 ', Blade::render('<x-chapter-opening title="x" level="9" />'));
    }

    #[Test]
    public function the_label_turns_brass_on_evergreen(): void
    {
        $this->assertStringContainsString('chapter-opening__label text-brass', Blade::render('<x-chapter-opening :number="1" title="x" tone="ivory" />'));
    }

    #[Test]
    public function epigraph_and_intro_slots_follow_the_heading(): void
    {
        // Blade needs whitespace after </x-slot:...> (it compiles to @endslot), as in the page templates.
        $html = Blade::render("<x-chapter-opening :number=\"1\" title=\"Life\"><x-slot:epigraph><x-epigraph>A quote.</x-epigraph></x-slot:epigraph>\nAn intro.</x-chapter-opening>");

        $this->assertMatchesRegularExpression('/<\/h2>\s*<div class="chapter-opening__epigraph" data-rise>\s*<figure class="epigraph text-ash">.*A quote\..*<\/figure>\s*<\/div>\s*<div class="t-body mt-6 max-w-2xl text-ash" data-rise>\s*An intro\.<\/div>/s', $html);
    }

    #[Test]
    public function a_zero_or_missing_number_shows_no_numeral(): void
    {
        $this->assertStringNotContainsString('t-numeral', Blade::render('<x-chapter-opening :number="0" label="x" title="y" />'));
    }
}
