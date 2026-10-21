<?php

namespace Tests\Feature\Components;

use App\Constants\BrandMark;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FolioTest extends TestCase
{
    #[Test]
    public function the_running_head_sets_the_name_and_section_in_small_caps_around_a_rule(): void
    {
        $html = Blade::render('<x-folio section="About" />');

        $this->assertMatchesRegularExpression('/<div class="folio folio--top" aria-hidden="true">\s*<span class="smallcaps hidden sm:inline">'.preg_quote(BrandMark::TITLE, '/').'<\/span>\s*<span class="folio__rule"><\/span>\s*<span class="smallcaps">About<\/span>/', $html);
    }

    #[Test]
    public function the_running_foot_reverses_the_order_and_the_ivory_tone_is_supported(): void
    {
        $html = Blade::render('<x-folio section="Tributes" position="bottom" tone="ivory" />');

        $this->assertStringContainsString('class="folio folio--bottom folio--ivory"', $html);
        $this->assertMatchesRegularExpression('/<span class="smallcaps">Tributes<\/span>\s*<span class="folio__rule"><\/span>\s*<span class="smallcaps hidden sm:inline">/', $html);
    }
}
