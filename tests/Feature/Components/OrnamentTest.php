<?php

namespace Tests\Feature\Components;

use App\Constants\Ornaments;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrnamentTest extends TestCase
{
    #[Test]
    public function every_ornament_renders_as_a_decorative_separator_of_hairline_shapes(): void
    {
        foreach (Ornaments::definitions() as $type => $ornament) {
            $html = Blade::render('<x-ornament :type="$type" />', ['type' => $type]);

            $this->assertStringContainsString('class="ornament ornament--'.$type.'" role="separator" data-ornament="'.$type.'" data-rise', $html);
            $this->assertStringContainsString('width="'.$ornament['width'].'" height="'.$ornament['height'].'" viewBox="0 0 '.$ornament['width'].' '.$ornament['height'].'" aria-hidden="true" focusable="false"', $html);
            $this->assertSame(count($ornament['shapes']), preg_match_all('/<(line|circle|path) /', $html));
            $this->assertStringNotContainsString('fill=', $html, 'Ornaments are never filled');
            $this->assertStringNotContainsString('<rect', $html);
        }
    }

    #[Test]
    public function an_unknown_type_falls_back_to_the_rule_with_a_dot_and_start_alignment_is_supported(): void
    {
        $html = Blade::render('<x-ornament type="sparkle" align="start" />');

        $this->assertStringContainsString('ornament--'.Ornaments::RULE_DOT, $html);
        $this->assertStringContainsString('ornament--start', $html);
    }

    #[Test]
    public function the_stylesheet_draws_them_with_one_pixel_brass_strokes_and_no_fill(): void
    {
        $css = file_get_contents(resource_path('css/typography.css'));

        $this->assertMatchesRegularExpression('/\.ornament svg \* \{[^}]*fill: none;[^}]*stroke: var\(--color-brass\);[^}]*stroke-width: 1px;[^}]*vector-effect: non-scaling-stroke;/s', $css);
    }
}
