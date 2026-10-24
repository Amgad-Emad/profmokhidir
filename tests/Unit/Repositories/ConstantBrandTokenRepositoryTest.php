<?php

namespace Tests\Unit\Repositories;

use App\Constants\Brand;
use App\Constants\Ornaments;
use App\Constants\Typesetting;
use App\Repositories\Contracts\BrandTokenRepositoryInterface;
use App\Repositories\Eloquent\ConstantBrandTokenRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConstantBrandTokenRepositoryTest extends TestCase
{
    private BrandTokenRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(BrandTokenRepositoryInterface::class);
    }

    #[Test]
    public function the_interface_is_bound_to_the_constant_implementation(): void
    {
        $this->assertInstanceOf(ConstantBrandTokenRepository::class, $this->repository);
    }

    #[Test]
    public function it_returns_exactly_the_two_functional_danger_colours_with_their_roles(): void
    {
        $this->assertSame(['danger-light' => '#9B2C2C', 'danger-dark' => '#EFB0A5'], $this->repository->functionalColours());
        $this->assertSame(array_keys($this->repository->functionalColours()), array_keys($this->repository->functionalColourRoles()));
    }

    #[Test]
    public function it_returns_exactly_the_nine_brand_colours(): void
    {
        $this->assertSame([
            'evergreen' => '#13221C',
            'ivory' => '#F3F1EA',
            'cypress' => '#2F4A3E',
            'brass' => '#A8864F',
            'champagne' => '#DCCDA8',
            'linen' => '#E7E5DC',
            'stone' => '#D3D1C5',
            'ash' => '#575A55',
            'bronze' => '#6F5A34',
        ], $this->repository->colours());
    }

    #[Test]
    public function every_colour_has_a_role(): void
    {
        $this->assertSame(array_keys(Brand::colours()), array_keys($this->repository->colourRoles()));
    }

    #[Test]
    public function it_returns_the_three_font_tokens_with_brand_weights(): void
    {
        $fonts = $this->repository->fonts();

        $this->assertSame(['display', 'sans', 'mono'], array_keys($fonts));
        $this->assertSame('Cormorant Garamond', $fonts['display']['family']);
        $this->assertSame([300], $fonts['display']['weights']);
        $this->assertTrue($fonts['display']['italic']);
        $this->assertSame([300, 400, 500], $fonts['sans']['weights']);
        $this->assertSame([400], $fonts['mono']['weights']);
    }

    #[Test]
    public function the_type_scale_matches_the_brand_guide(): void
    {
        $scale = $this->repository->typeScale();

        $this->assertSame(['display', 'heading', 'quote', 'body', 'label'], array_keys($scale));
        $this->assertSame(96, $scale['display']['size']);
        $this->assertSame(48, $scale['heading']['size']);
        $this->assertSame(32, $scale['quote']['size']);
        $this->assertTrue($scale['quote']['italic']);
        $this->assertSame(18, $scale['body']['size']);
        $this->assertSame(12, $scale['label']['size']);
        $this->assertSame('0.3em', $scale['label']['tracking']);
        $this->assertTrue($scale['label']['uppercase']);
    }

    #[Test]
    public function it_describes_the_five_motion_behaviours(): void
    {
        $motion = $this->repository->motion();

        $this->assertSame(['rise', 'rise-group', 'rule', 'scroll-scale', 'scroll-line'], array_keys($motion));
        $this->assertSame('data-rise', $motion['rise']['attribute']);
        $this->assertStringContainsString('1200ms', $motion['rise']['summary']);
        $this->assertStringContainsString('120ms', $motion['rise-group']['summary']);
        $this->assertStringContainsString('1600ms', $motion['rule']['summary']);
        $this->assertStringContainsString('0.7', $motion['scroll-scale']['summary']);
        $this->assertSame('data-scroll-line', $motion['scroll-line']['attribute']);
    }

    #[Test]
    public function it_serves_the_typesetting_rules_features_rhythm_and_ornaments(): void
    {
        $rules = $this->repository->typesettingRules();
        $this->assertSame(Typesetting::units(), $rules['units']);
        $this->assertSame(Typesetting::bindingAbbreviations(), $rules['bindingAbbreviations']);
        $this->assertSame(Typesetting::capsExceptions(), $rules['capsExceptions']);

        $typesetting = $this->repository->typesetting();
        $this->assertSame(Typesetting::features(), $typesetting['features']);
        $this->assertSame(8, $typesetting['baseline']);
        $this->assertSame(32, $typesetting['bodyLine']);
        $this->assertSame(['min' => 60, 'max' => 72], $typesetting['measure']);
        $this->assertSame(0, $typesetting['bodyLine'] % $typesetting['baseline'], 'Body lines sit on the baseline grid');

        $this->assertSame(Ornaments::definitions(), $this->repository->ornaments());
        $this->assertSame([Ornaments::RULE_DOT, Ornaments::DOUBLE_RULE, Ornaments::FLEURON], array_keys($this->repository->ornaments()));
    }
}
