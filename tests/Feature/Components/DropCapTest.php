<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DropCapTest extends TestCase
{
    #[Test]
    public function it_wraps_prose_so_the_first_paragraph_opens_with_an_initial(): void
    {
        $html = Blade::render('<x-drop-cap class="prose-bio"><p>First.</p><p>Second.</p></x-drop-cap>');

        $this->assertSame('<div class="drop-cap prose-bio"><p>First.</p><p>Second.</p></div>', trim($html));
    }

    #[Test]
    public function the_ivory_tone_sets_the_initial_in_champagne(): void
    {
        $this->assertStringContainsString('class="drop-cap drop-cap--ivory"', Blade::render('<x-drop-cap tone="ivory"><p>x</p></x-drop-cap>'));
        $this->assertStringNotContainsString('drop-cap--ivory', Blade::render('<x-drop-cap><p>x</p></x-drop-cap>'));
    }

    #[Test]
    public function the_stylesheet_spans_three_lines_with_initial_letter_and_a_float_fallback(): void
    {
        $css = file_get_contents(resource_path('css/typography.css'));

        $this->assertStringContainsString('.drop-cap > p:first-of-type::first-letter', $css);
        $this->assertStringContainsString('initial-letter: 3;', $css);
        $this->assertStringContainsString('float: inline-start;', $css);
        $this->assertStringContainsString('color: var(--ink);', $css);
    }

    #[Test]
    public function in_arabic_the_first_word_is_emphasised_instead_of_a_dropped_initial(): void
    {
        app()->setLocale('ar');

        $html = Blade::render('<x-drop-cap class="prose-bio"><h2>عنوان</h2><p class="x">كان الأستاذ معلمًا.</p><p>فقرة ثانية.</p></x-drop-cap>');

        $this->assertSame('<div class="drop-cap prose-bio"><h2>عنوان</h2><p class="x"><span class="drop-cap__lead">كان</span> الأستاذ معلمًا.</p><p>فقرة ثانية.</p></div>', trim($html));

        $css = file_get_contents(resource_path('css/locale.css'));
        $this->assertStringContainsString('.drop-cap:lang(ar) > p:first-of-type::first-letter', $css);
        $this->assertMatchesRegularExpression('/\.drop-cap__lead:lang\(ar\) \{[^}]*font-weight: 700;/', $css);
    }
}
