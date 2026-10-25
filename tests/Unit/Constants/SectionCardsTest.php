<?php

namespace Tests\Unit\Constants;

use App\Constants\SectionCards;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The section card constants and resources/css/section-cards.css describe the same card.
 */
class SectionCardsTest extends TestCase
{
    private string $css;

    protected function setUp(): void
    {
        parent::setUp();

        $this->css = file_get_contents(resource_path('css/section-cards.css'));
    }

    private function rem(int $px): string
    {
        return rtrim(rtrim(number_format($px / 16, 4, '.', ''), '0'), '.').'rem';
    }

    #[Test]
    public function the_grid_fills_the_width_with_auto_fit_columns_of_at_least_280px_24px_apart(): void
    {
        $this->assertSame(280, SectionCards::MIN_COLUMN_PX);
        $this->assertStringContainsString('grid-template-columns: repeat(auto-fit, minmax(min('.$this->rem(SectionCards::MIN_COLUMN_PX).', 100%), 1fr));', $this->css);
        $this->assertStringContainsString('gap: '.$this->rem(SectionCards::GAP_PX).';', $this->css);
    }

    #[Test]
    public function the_card_is_padded_28px_on_desktop_and_24px_on_phones_with_a_28px_heading_and_17px_intro(): void
    {
        $this->assertStringContainsString('padding: '.$this->rem(SectionCards::PADDING_MOBILE_PX).';', $this->css);
        $this->assertStringContainsString('padding: '.$this->rem(SectionCards::PADDING_PX).';', $this->css);
        $this->assertStringContainsString('font-size: '.$this->rem(SectionCards::TITLE_PX).'; /* 28px */', $this->css);
        $this->assertStringContainsString('font-size: '.$this->rem(SectionCards::SUMMARY_PX).'; /* 17px */', $this->css);
    }

    #[Test]
    public function hover_is_a_brass_hairline_a_2px_rise_and_a_4px_arrow_only_when_motion_is_welcome(): void
    {
        $this->assertStringContainsString('border-color: var(--accent-line);', $this->css);

        [$before, $motion] = explode('@media (prefers-reduced-motion: no-preference)', $this->css, 2);

        $this->assertStringNotContainsString('transform', $before, 'nothing moves outside the motion query');
        $this->assertStringContainsString('transform: translateY(-'.SectionCards::HOVER_RISE_PX.'px);', $motion);
        $this->assertStringContainsString('transform: translateX('.SectionCards::ARROW_SHIFT_PX.'px);', $motion);
    }
}
