<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MuseumLabelTest extends TestCase
{
    #[Test]
    public function it_sets_title_place_and_year_and_note_like_a_museum_label(): void
    {
        $html = Blade::render('<x-museum-label title="Gezira research station" meta="Wad Madani, 1972" note="With the first cohort." class="mt-4" />');

        $this->assertMatchesRegularExpression('/<figcaption class="museum-label mt-4">\s*<span class="museum-label__title">Gezira research station<\/span>\s*<span class="museum-label__meta smallcaps">Wad Madani, 1972<\/span>\s*<span class="museum-label__note">With the first cohort.<\/span>\s*<\/figcaption>/', $html);
    }

    #[Test]
    public function it_renders_only_populated_lines_in_the_chosen_element_and_ink(): void
    {
        $html = Blade::render('<x-museum-label meta="1980" tone="ivory" as="p" />');

        $this->assertStringContainsString('<p class="museum-label museum-label--ivory">', $html);
        $this->assertStringContainsString('<span class="museum-label__meta smallcaps">1980</span>', $html);
        $this->assertStringNotContainsString('museum-label__title', $html);
        $this->assertStringNotContainsString('museum-label__note', $html);
        $this->assertStringContainsString('</p>', $html);

        $this->assertSame('', trim(Blade::render('<x-museum-label />')));
    }
}
