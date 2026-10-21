<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TypesetDirectiveTest extends TestCase
{
    #[Test]
    public function it_echoes_typeset_and_escaped_text(): void
    {
        $html = Blade::render('<h3>@typeset($title)</h3>', ['title' => 'The FAO "years", 1975-1992 <script>']);

        $this->assertSame('<h3>The <span class="caps">FAO</span> “years”, 1975–1992 &lt;script&gt;</h3>', $html);
    }

    #[Test]
    public function null_renders_nothing(): void
    {
        $this->assertSame('<p></p>', Blade::render('<p>@typeset($value)</p>', ['value' => null]));
    }
}
