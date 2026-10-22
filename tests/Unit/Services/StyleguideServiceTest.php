<?php

namespace Tests\Unit\Services;

use App\Constants\Brand;
use App\Constants\Components;
use App\Services\StyleguideService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StyleguideServiceTest extends TestCase
{
    private StyleguideService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(StyleguideService::class);
    }

    #[Test]
    public function it_computes_wcag_contrast_ratios(): void
    {
        $this->assertSame(21.0, $this->service->contrastRatio('#000000', '#FFFFFF'));
        $this->assertSame(1.0, $this->service->contrastRatio('#A8864F', '#a8864f'));
        $this->assertSame(
            $this->service->contrastRatio(Brand::IVORY, Brand::EVERGREEN),
            $this->service->contrastRatio(Brand::EVERGREEN, Brand::IVORY),
            'Contrast is symmetric',
        );
        $this->assertSame(0.0, $this->service->relativeLuminance('#000'));
        $this->assertSame(1.0, $this->service->relativeLuminance('#fff'));
    }

    #[Test]
    public function it_rejects_values_that_are_not_hex_colours(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->contrastRatio('evergreen', Brand::IVORY);
    }

    #[Test]
    public function the_primary_pairings_pass_aa_for_normal_text(): void
    {
        $this->assertTrue($this->service->contrast(Brand::EVERGREEN, Brand::IVORY)['normal']);
        $this->assertTrue($this->service->contrast(Brand::CHAMPAGNE, Brand::EVERGREEN)['normal']);
        $this->assertTrue($this->service->contrast(Brand::ASH, Brand::IVORY)['normal']);
        $this->assertTrue($this->service->contrast(Brand::BRONZE, Brand::IVORY)['normal']);
    }

    #[Test]
    public function brass_on_ivory_fails_normal_text_which_is_why_bronze_exists(): void
    {
        $brass = $this->service->contrast(Brand::BRASS, Brand::IVORY);
        $bronze = $this->service->contrast(Brand::BRONZE, Brand::IVORY);

        $this->assertFalse($brass['normal']);
        $this->assertGreaterThan($brass['ratio'], $bronze['ratio']);
        $this->assertTrue($this->service->contrast(Brand::BRASS, Brand::EVERGREEN)['large'], 'Brass labels are fine on evergreen');
    }

    #[Test]
    public function colours_lists_each_brand_colour_once_with_its_contrast(): void
    {
        $rows = $this->service->colours();

        $this->assertCount(9, $rows);
        $this->assertSame(array_keys(Brand::colours()), array_column($rows, 'name'));

        foreach ($rows as $row) {
            $this->assertTrue(Brand::isBrandColour($row['hex']));
            $this->assertNotSame('', $row['role']);
            $this->assertArrayHasKey('ratio', $row['onIvory']);
            $this->assertArrayHasKey('ratio', $row['onEvergreen']);
        }
    }

    #[Test]
    public function sections_contains_everything_the_view_needs(): void
    {
        $sections = $this->service->sections();

        $this->assertSame(['colours', 'fonts', 'typeScale', 'motion', 'typesetting', 'ornaments', 'typesetExamples', 'components'], array_keys($sections));
        $this->assertSame(array_keys(Components::groups()), array_keys($sections['components']));
        $this->assertCount(count(Components::all()), array_merge(...array_column($sections['components'], 'components')));
        $this->assertCount(9, $sections['colours']);
        $this->assertCount(3, $sections['fonts']);
        $this->assertCount(5, $sections['typeScale']);
        $this->assertCount(5, $sections['motion']);
    }

    #[Test]
    public function it_shows_the_typesetter_before_and_after_and_the_ornaments(): void
    {
        $examples = $this->service->typesetExamples();

        $this->assertNotEmpty($examples);
        foreach ($examples as $example) {
            $this->assertNotSame($example['before'], $example['after'], 'Every sample shows a change');
        }
        $this->assertStringContainsString('1975–1992', implode(' ', array_column($examples, 'after')));
        $this->assertStringContainsString('<span class="caps">FAO</span>', implode(' ', array_column($examples, 'after')));
        $this->assertCount(3, $this->service->ornaments());
        $this->assertArrayHasKey('features', $this->service->typesetting());
    }
}
