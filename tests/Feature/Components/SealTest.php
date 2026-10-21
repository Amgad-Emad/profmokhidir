<?php

namespace Tests\Feature\Components;

use App\Constants\BrandMark;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SealTest extends TestCase
{
    #[Test]
    public function it_closes_with_the_ringed_mark_above_a_short_rule(): void
    {
        $html = Blade::render('<x-seal />');

        $this->assertMatchesRegularExpression('/<div class="seal" aria-hidden="true" data-rise>\s*<svg class="brand-mark brand-mark--ring"[^>]*data-size="40" data-tone="evergreen" data-variant="ring" aria-hidden="true"[^>]*>.*<\/svg>\s*<span class="seal__rule"><\/span>/s', $html);
        $this->assertStringContainsString('style="padding: '.BrandMark::clearSpace(40).'px"', $html, 'The mark keeps its clear space');
    }

    #[Test]
    public function it_never_shrinks_to_the_disc_and_follows_the_tone(): void
    {
        $html = Blade::render('<x-seal :size="16" tone="ivory" />');

        $this->assertStringContainsString('data-size="'.BrandMark::DISC_BELOW_PX.'" data-tone="ivory" data-variant="ring"', $html);
    }
}
