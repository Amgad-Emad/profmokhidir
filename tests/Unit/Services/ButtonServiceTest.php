<?php

namespace Tests\Unit\Services;

use App\Constants\ButtonSize;
use App\Constants\ButtonState;
use App\Constants\ButtonVariant;
use App\Services\ButtonService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ButtonServiceTest extends TestCase
{
    private ButtonService $buttons;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buttons = new ButtonService;
    }

    #[Test]
    public function each_variant_maps_to_the_base_class_and_its_modifier(): void
    {
        foreach (ButtonVariant::cases() as $variant) {
            $this->assertSame('btn btn--'.$variant->value, $this->buttons->classes($variant->value));
        }
    }

    #[Test]
    public function size_and_the_dark_band_add_their_classes(): void
    {
        $this->assertSame('btn btn--primary btn--small', $this->buttons->classes('primary', 'sm'));
        $this->assertSame('btn btn--primary btn--round', $this->buttons->classes(ButtonVariant::Primary, ButtonSize::Round));
        $this->assertSame('btn btn--secondary on-dark', $this->buttons->classes('secondary', null, true));
    }

    #[Test]
    public function unknown_variants_and_sizes_are_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown button variant [outline]');

        $this->buttons->classes('outline');
    }

    #[Test]
    public function an_unknown_size_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->buttons->classes('primary', 'huge');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function colourUtilities(): array
    {
        return [
            'text ink' => ['text-ink'],
            'hover text' => ['mt-4 hover:text-ink-strong'],
            'background' => ['bg-surface-raised'],
            'hover background' => ['hover:bg-fill'],
            'border' => ['border-rule'],
            'danger tone' => ['text-danger-tone underline'],
        ];
    }

    #[Test]
    #[DataProvider('colourUtilities')]
    public function colour_utilities_on_a_button_are_refused(string $classes): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->buttons->assertNoColourUtilities($classes);
    }

    #[Test]
    public function layout_utilities_are_allowed(): void
    {
        $this->buttons->assertNoColourUtilities('w-full mt-8 text-sm text-center hidden xl:inline-flex border');
        $this->buttons->assertNoColourUtilities(null);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function token_names_follow_variant_part_and_state(): void
    {
        $this->assertSame('--btn-primary-bg', ButtonVariant::Primary->token('bg'));
        $this->assertSame('--btn-secondary-ink-hover', ButtonVariant::Secondary->token('ink', ButtonState::Hover));
        $this->assertSame('--btn-danger-border-focus', ButtonVariant::Danger->token('border', ButtonState::FocusVisible));
        $this->assertTrue(ButtonVariant::Danger->hasBoundary());
        $this->assertFalse(ButtonVariant::Icon->hasBoundary());
    }
}
