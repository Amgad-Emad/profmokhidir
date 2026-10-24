<?php

namespace Tests\Unit\Design;

use App\Constants\ButtonState;
use App\Constants\ButtonVariant;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Unit\Design\Concerns\ResolvesDesignTokens;

/**
 * Every button variant in every state, in both themes and on every surface a
 * button can stand on (the page, a raised card, an .on-dark band, the hero),
 * computed from the .btn token table in resources/css/tokens.css: the label and
 * the icon reach 4.5:1 against the button's own background (composited over the
 * surface), outline pills keep a 3:1 boundary, and hover never changes the ink
 * of a solid pill. The quiet danger tone (row actions) is checked as well.
 */
class ButtonContrastTest extends TestCase
{
    use ResolvesDesignTokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadTokens();
    }

    #[Test]
    public function the_token_table_declares_every_part_of_every_variant_in_every_state(): void
    {
        $missing = [];

        foreach (ButtonVariant::cases() as $variant) {
            foreach (ButtonState::cases() as $state) {
                foreach (ButtonVariant::PARTS as $part) {
                    if (! array_key_exists($variant->token($part, $state), $this->blocks['.btn'] ?? [])) {
                        $missing[] = $variant->token($part, $state);
                    }
                }
            }
        }

        $this->assertSame([], $missing, 'Missing button tokens in tokens.css (.btn)');
    }

    #[Test]
    public function the_stylesheet_applies_every_state_token_of_every_variant(): void
    {
        $css = file_get_contents(resource_path('css/buttons.css'));

        foreach (ButtonVariant::cases() as $variant) {
            foreach (ButtonState::cases() as $state) {
                foreach (ButtonVariant::PARTS as $part) {
                    $this->assertStringContainsString('var('.$variant->token($part, $state).')', $css, "buttons.css never applies {$variant->token($part, $state)}");
                }
            }
        }

        $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}\b|--brand-/i', $css, 'buttons.css names a colour: use the .btn tokens');
        $this->assertDoesNotMatchRegularExpression('/opacity\s*:/', $css, 'A button state must not fade the label with opacity');
    }

    #[Test]
    public function labels_and_icons_reach_4_5_to_1_in_every_variant_state_theme_and_surface(): void
    {
        $failures = [];
        $checked = 0;

        foreach (self::contexts() as $label => $context) {
            foreach ($this->surfaces($context) as $surfaceName => $surface) {
                foreach ($this->combinations() as $name => [$variant, $inkVariant]) {
                    foreach (ButtonState::cases() as $state) {
                        $background = $this->over($this->resolve(substr($variant->token('bg', $state), 2), $context), $surface);

                        foreach (['ink', 'icon'] as $part) {
                            $ratio = $this->ratio($this->resolve(substr($inkVariant->token($part, $state), 2), $context), $background);
                            $checked++;

                            if ($ratio < ButtonVariant::LABEL_CONTRAST) {
                                $failures[] = sprintf('%s on --%s: %s %s %s is %.2f:1', $label, $surfaceName, $name, $state->value, $part, $ratio);
                            }
                        }
                    }
                }
            }
        }

        $this->assertGreaterThan(1000, $checked);
        $this->assertSame([], $failures, "Button labels below 4.5:1:\n".implode("\n", $failures));
    }

    #[Test]
    public function outline_pills_keep_a_3_to_1_boundary_against_the_surface(): void
    {
        $failures = [];

        foreach (self::contexts() as $label => $context) {
            foreach ($this->surfaces($context) as $surfaceName => $surface) {
                foreach (ButtonVariant::cases() as $variant) {
                    if (! $variant->hasBoundary()) {
                        continue;
                    }

                    foreach (ButtonState::cases() as $state) {
                        $ratio = $this->ratio($this->resolve(substr($variant->token('border', $state), 2), $context), $surface);

                        if ($ratio < ButtonVariant::BORDER_CONTRAST) {
                            $failures[] = sprintf('%s on --%s: %s %s border is %.2f:1', $label, $surfaceName, $variant->value, $state->value, $ratio);
                        }
                    }
                }
            }
        }

        $this->assertSame([], $failures, "Button outlines below 3:1:\n".implode("\n", $failures));
    }

    #[Test]
    public function hover_is_subtle_and_keeps_the_ink_of_the_solid_pill(): void
    {
        foreach (self::contexts() as $label => $context) {
            $surface = $this->resolve($context['raised'] ? 'surface-raised' : 'surface', $context);
            $variant = ButtonVariant::Primary;

            $ink = $this->hex($this->resolve(substr($variant->token('ink'), 2), $context));
            $default = $this->over($this->resolve(substr($variant->token('bg'), 2), $context), $surface);
            $hover = $this->over($this->resolve(substr($variant->token('bg', ButtonState::Hover), 2), $context), $surface);

            $this->assertSame($ink, $this->hex($this->resolve(substr($variant->token('ink', ButtonState::Hover), 2), $context)), "{$label}: hover changes the solid pill's ink");
            $this->assertNotSame($this->hex($default), $this->hex($hover), "{$label}: hover does not change the solid pill");
            $this->assertLessThan(1.35, $this->ratio($hover, $default), "{$label}: the solid pill's hover is not subtle");
        }
    }

    #[Test]
    public function the_reported_cases_hold_evergreen_pill_on_ivory_and_ivory_pill_in_the_dark(): void
    {
        $light = ['theme' => 'light', 'layers' => [], 'raised' => true];
        $dark = ['theme' => 'dark', 'layers' => [], 'raised' => false];

        foreach ([$light, $dark] as $context) {
            $surface = $this->resolve($context['raised'] ? 'surface-raised' : 'surface', $context);

            foreach ([ButtonVariant::Primary, ButtonVariant::Secondary] as $variant) {
                $background = $this->over($this->resolve(substr($variant->token('bg', ButtonState::Hover), 2), $context), $surface);
                $ink = $this->resolve(substr($variant->token('ink', ButtonState::Hover), 2), $context);

                $this->assertGreaterThanOrEqual(7.0, $this->ratio($ink, $background), "{$context['theme']}: {$variant->value} on hover");
            }
        }
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Each variant, plus the quiet button in the danger tone (its background follows
     * quiet, its ink follows danger).
     *
     * @return array<string, array{ButtonVariant, ButtonVariant}>
     */
    private function combinations(): array
    {
        $combinations = [];

        foreach (ButtonVariant::cases() as $variant) {
            $combinations[$variant->value] = [$variant, $variant];
        }

        $combinations['quiet danger'] = [ButtonVariant::Quiet, ButtonVariant::Danger];

        return $combinations;
    }

    /**
     * The opaque surfaces a button can stand on in a context.
     *
     * @return array<string, array{float, float, float, float}>
     */
    private function surfaces(array $context): array
    {
        $names = $context['raised'] ? ['surface-raised', 'surface-panel'] : ['surface', 'surface-sunken'];

        return array_combine($names, array_map(fn (string $name): array => $this->over($this->resolve($name, $context), [1, 1, 1, 1]), $names));
    }
}
