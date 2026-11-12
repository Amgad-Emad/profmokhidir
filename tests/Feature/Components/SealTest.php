<?php

namespace Tests\Feature\Components;

use App\Constants\BrandMark;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SealTest extends TestCase
{
    #[Test]
    public function it_closes_with_the_mark_above_a_short_rule(): void
    {
        $html = Blade::render('<x-seal />');

        $this->assertMatchesRegularExpression('/<div class="seal" aria-hidden="true" data-rise>\s*<span class="brand-mark brand-mark--full"[^>]*data-size="40" data-tone="evergreen" data-variant="full" aria-hidden="true"[^>]*>.*<\/span>\s*<span class="seal__rule"><\/span>/s', $html);
        $this->assertStringContainsString('style="padding: '.BrandMark::clearSpace(40).'px; --brand-mark-height: 40px"', $html, 'The mark keeps its clear space');
    }

    #[Test]
    public function it_never_shrinks_to_the_icon_and_follows_the_tone(): void
    {
        $html = Blade::render('<x-seal :size="16" tone="ivory" />');

        $this->assertStringContainsString('data-size="'.BrandMark::DISC_BELOW_PX.'" data-tone="ivory" data-variant="full"', $html);
    }
}
