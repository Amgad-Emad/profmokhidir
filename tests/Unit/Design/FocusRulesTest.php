<?php

namespace Tests\Unit\Design;

use App\Constants\FormControls;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Focus is drawn in two ways only: text entry shows it with its own line (forms.css),
 * everything else with a 2px keyboard-only ring offset 2px (app.css). No stylesheet
 * puts a ring, outline or box around a text field, and nothing tracks input modality.
 */
class FocusRulesTest extends TestCase
{
    private function css(string $file): string
    {
        return (string) file_get_contents(resource_path('css/'.$file));
    }

    /**
     * Every rule in a stylesheet whose selector mentions focus, as selector => body.
     *
     * @return array<string, string>
     */
    private function focusRules(string $css): array
    {
        $css = (string) preg_replace('/\/\*.*?\*\//s', '', $css);
        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER);

        $rules = [];
        foreach ($matches as [, $selector, $body]) {
            if (str_contains($selector, 'focus')) {
                $rules[trim($selector)] = $body;
            }
        }

        return $rules;
    }

    #[Test]
    public function the_shared_ring_is_two_pixels_offset_two_for_the_keyboard_only(): void
    {
        $app = $this->css('app.css');

        $this->assertMatchesRegularExpression('/\n    :focus-visible \{\s*outline: '.FormControls::FOCUS_RING_PX.'px solid var\(--focus\);\s*outline-offset: '.FormControls::FOCUS_OFFSET_PX.'px;/', $app);
        $this->assertDoesNotMatchRegularExpression('/\n    :focus \{/', $app, 'no ring on a plain (mouse) focus');
    }

    #[Test]
    public function text_entry_withdraws_the_ring_everywhere(): void
    {
        $app = $this->css('app.css');

        foreach (["[type='checkbox']", "[type='radio']", "[type='button']", "[type='submit']", 'textarea,', 'select,', "[contenteditable]:not([contenteditable='false'])"] as $part) {
            $this->assertStringContainsString($part, $app);
        }
        $this->assertMatchesRegularExpression('/\):is\(:focus, :focus-visible\) \{\s*outline: none;\s*\}/', $app);
    }

    #[Test]
    public function the_form_frame_shows_focus_with_its_line_and_a_tint_never_a_ring(): void
    {
        $forms = $this->css('forms.css');

        $this->assertStringContainsString('transition:'."\n".'            border-color '.FormControls::FOCUS_LINE_MS.'ms', $forms);
        $this->assertMatchesRegularExpression('/\.form-control:focus-within,\s*\.form-control\[data-demo-state=\'focus\'\] \{\s*--form-line: var\(--field-focus-line\);\s*--form-line-extra: 1px;\s*background-color: color-mix\(in srgb, var\(--ink\) 5%, var\(--form-surface\)\);/', $forms);
        $this->assertStringContainsString('box-shadow: inset 0 calc(var(--form-line-extra) * -1) 0 var(--form-line);', $forms, 'the second pixel sits along the foot only');

        foreach ($this->focusRules($forms) as $selector => $body) {
            $this->assertDoesNotMatchRegularExpression('/outline:\s*+(?!none)/', $body, "{$selector} draws an outline");
            $this->assertDoesNotMatchRegularExpression('/box-shadow:\s*+(?!none)/', $body, "{$selector} draws a shadow");
        }
    }

    #[Test]
    public function no_stylesheet_draws_a_focus_ring_with_a_spread_shadow_or_tracks_modality(): void
    {
        foreach (glob(resource_path('css/*.css')) as $path) {
            $css = (string) file_get_contents($path);
            $name = basename($path);

            $this->assertStringNotContainsString('data-modality', $css, $name);

            foreach ($this->focusRules($css) as $selector => $body) {
                $this->assertDoesNotMatchRegularExpression('/box-shadow:\s*0 0 0 (?![^;]*inset)/', $body, "{$name}: {$selector} draws a ring with a shadow");
            }
        }

        $this->assertStringNotContainsString('modality', (string) file_get_contents(resource_path('js/forms.js')));
    }
}
