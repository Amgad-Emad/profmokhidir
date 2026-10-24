<?php

declare(strict_types=1);

namespace Tests\Feature\Styleguide;

use App\Constants\Environments;
use App\Constants\FormControls;
use App\Constants\FormStates;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StyleguideFormsTest extends TestCase
{
    private function html(): string
    {
        $this->app['env'] = Environments::LOCAL;

        return $this->get(route('styleguide'))->assertOk()->getContent();
    }

    #[Test]
    public function the_forms_section_is_linked_from_the_hero_and_explains_the_rules(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('<a href="#forms" class="btn btn--secondary">Forms</a>', $html);
        $this->assertStringContainsString('<section id="forms" class="py-20" aria-labelledby="forms-title" data-styleguide-forms>', $html);
        $this->assertStringContainsString(FormControls::MIN_HEIGHT_PX.'px or taller with '.FormControls::TEXT_SIZE_PX.'px text', $html);
        $this->assertStringContainsString('line turns from 1px muted to '.FormControls::FOCUS_LINE_PX.'px strong ink over '.FormControls::FOCUS_LINE_MS.'ms', $html);
        $this->assertStringContainsString('no outline, ring or box', $html);
        $this->assertStringContainsString(FormControls::FOCUS_RING_PX.'px ring offset '.FormControls::FOCUS_OFFSET_PX.'px for the keyboard only, never over the label', $html);
    }

    #[Test]
    public function every_state_is_shown_in_light_and_dark_in_both_frame_styles(): void
    {
        $html = $this->html();

        foreach (['light', 'dark', 'underline-light', 'underline-dark'] as $specimen) {
            $this->assertStringContainsString('data-specimen="'.$specimen.'"', $html);

            foreach (FormStates::keys() as $state) {
                $this->assertStringContainsString('id="state-'.$state.'-'.$specimen.'"', $html, "{$state} in {$specimen}");
            }
        }

        $this->assertStringContainsString('class="form-specimen form-specimen--dark"', $html);
        $this->assertStringContainsString('data-demo-state="focus"', $html);
        $this->assertStringNotContainsString('focus-keyboard', $html);
        $this->assertStringContainsString('data-corrected="data-corrected"', $html);
        $this->assertStringContainsString(FormControls::yearMessage(), $html);
    }

    #[Test]
    public function every_control_is_shown_in_light_and_dark(): void
    {
        $html = $this->html();

        foreach (['light', 'dark'] as $theme) {
            $this->assertStringContainsString('data-specimen="controls-'.$theme.'"', $html);

            foreach (['name', 'email', 'message', 'relationship', 'album', 'date', 'year', 'topics', 'password', 'agree', 'featured', 'portrait', 'bio'] as $control) {
                $this->assertStringContainsString('id="'.$control.'-'.$theme.'"', $html, "{$control} in {$theme}");
            }

            $this->assertStringContainsString('id="code-'.$theme.'-1"', $html);
            $this->assertStringContainsString('id="status-'.$theme.'-draft"', $html);
            $this->assertStringContainsString('id="type-'.$theme.'-book"', $html);
        }

        foreach (['x-data="formCombobox(', 'x-data="formTags(', 'x-data="formUpload(', 'x-data="formEditor(', 'x-data="formOtp(', 'x-data="formPassword(', 'role="switch"', 'data-error-summary'] as $hook) {
            $this->assertStringContainsString($hook, $html);
        }

        $this->assertDoesNotMatchRegularExpression('/\swire:[a-z.]+="/', $html, 'The style guide is a public page: no Livewire bindings');
    }
}
