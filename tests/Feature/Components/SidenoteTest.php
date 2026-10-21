<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SidenoteTest extends TestCase
{
    #[Test]
    public function the_reference_is_a_button_described_by_and_controlling_its_note(): void
    {
        $html = Blade::render('<p>Text<x-sidenote :number="2" id="note-a">A marginal note.</x-sidenote> more.</p>');

        $this->assertStringContainsString('<button type="button" class="sidenote__ref" aria-describedby="note-a" aria-controls="note-a" aria-expanded="false" x-bind:aria-expanded="open.toString()" x-on:click="open = ! open"><span class="sr-only">Note </span>2</button>', $html);
        $this->assertStringContainsString('<span id="note-a" class="sidenote__note" role="note" x-bind:class="{ \'is-open\': open }"><span class="sidenote__number" aria-hidden="true">2</span>A marginal note.</span>', $html);
        $this->assertStringContainsString('<p>Text<span class="sidenote"', $html, 'No whitespace is added before the reference');
    }

    #[Test]
    public function ids_are_generated_and_differ_between_notes(): void
    {
        $html = Blade::render('<x-sidenote :number="1">One.</x-sidenote><x-sidenote :number="2">Two.</x-sidenote>');

        preg_match_all('/id="(sidenote-[^"]+)"/', $html, $ids);
        $this->assertCount(2, array_unique($ids[1]));
        $this->assertStringContainsString('<span class="sr-only">Note </span>*', Blade::render('<x-sidenote>Unnumbered.</x-sidenote>'));
    }

    #[Test]
    public function notes_move_to_the_margin_only_when_the_host_holds_the_measure_and_the_note(): void
    {
        $css = file_get_contents(resource_path('css/typography.css'));

        $this->assertStringContainsString('container: sidenotes / inline-size;', $css);
        $this->assertStringContainsString('@container sidenotes (min-width: 47rem)', $css);
        $this->assertMatchesRegularExpression('/@media \(scripting: enabled\) \{\s*\.sidenote__note:not\(\.is-open\) \{\s*display: none;/', $css, 'Hidden only when JavaScript can reveal it');
    }
}
