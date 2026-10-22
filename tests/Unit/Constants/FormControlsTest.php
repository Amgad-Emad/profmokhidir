<?php

declare(strict_types=1);

namespace Tests\Unit\Constants;

use App\Constants\FormControls;
use App\Constants\FormStates;
use App\Http\Requests\Admin\ContentRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FormControlsTest extends TestCase
{
    #[Test]
    public function controls_are_tall_enough_to_tap_and_large_enough_that_ios_never_zooms(): void
    {
        $this->assertGreaterThanOrEqual(48, FormControls::MIN_HEIGHT_PX);
        $this->assertGreaterThanOrEqual(16, FormControls::TEXT_SIZE_PX);
        $this->assertSame(2, FormControls::FOCUS_RING_PX);
        $this->assertSame(2, FormControls::FOCUS_OFFSET_PX);
    }

    #[Test]
    public function the_blank_placeholder_survives_blade_trimming(): void
    {
        $this->assertNotSame('', trim(FormControls::BLANK_PLACEHOLDER));
        $this->assertSame("\u{00A0}", FormControls::BLANK_PLACEHOLDER);
    }

    #[Test]
    public function the_editor_toolbar_has_every_tool_labelled_with_an_icon_and_a_shortcut(): void
    {
        $tools = FormControls::editorTools();

        $this->assertSame(['heading2', 'heading3', 'bold', 'italic', 'bulletList', 'orderedList', 'blockquote', 'link', 'undo', 'redo'], array_keys($tools));
        $this->assertSame(['Heading', 'Subheading', 'Bold', 'Italic', 'Bulleted list', 'Numbered list', 'Quote', 'Link', 'Undo', 'Redo'], array_column($tools, 'label'));

        foreach ($tools as $tool) {
            $this->assertNotSame('', $tool['icon']);
            $this->assertStringStartsWith('Mod-', $tool['shortcut']);
            $this->assertStringContainsString("@case('{$tool['icon']}')", (string) file_get_contents(resource_path('views/components/icon.blade.php')), "Icon {$tool['icon']} exists");
        }

        $this->assertSame('Ctrl+Shift+8', FormControls::shortcutLabel('Mod-Shift-8'));
    }

    #[Test]
    public function the_year_message_names_the_range_the_rule_enforces(): void
    {
        $max = FormControls::yearMax();
        $this->assertSame((int) now()->addYear()->format('Y'), $max);
        $this->assertSame("Enter a year between 1900 and {$max}.", FormControls::yearMessage());
        $this->assertSame('Enter a year between 1950 and 2000.', FormControls::yearMessage(1950, 2000));

        $validator = Validator::make(
            ['form' => ['year' => '1850', 'start_year' => 'abc'], 'details' => ['year' => (string) ($max + 5)]],
            ['form.year' => ContentRequest::yearRule(), 'form.start_year' => ContentRequest::yearRule(), 'details.year' => ContentRequest::yearRule()],
        );

        foreach (['form.year', 'form.start_year', 'details.year'] as $key) {
            $this->assertSame(FormControls::yearMessage(), $validator->errors()->first($key), $key);
        }
    }

    #[Test]
    public function default_messages_name_the_fix(): void
    {
        $validator = Validator::make(
            ['email' => 'not-an-email', 'website' => 'example', 'title' => str_repeat('x', 12)],
            ['name' => 'required', 'email' => 'email', 'website' => 'url', 'title' => 'max:10'],
        );

        $this->assertSame('Enter name.', $validator->errors()->first('name'));
        $this->assertSame(FormControls::MESSAGE_EMAIL, $validator->errors()->first('email'));
        $this->assertSame(FormControls::MESSAGE_URL, $validator->errors()->first('website'));
        $this->assertSame('Shorten title to 10 characters or fewer.', $validator->errors()->first('title'));
    }

    #[Test]
    public function every_state_is_listed_with_its_look_and_hook(): void
    {
        $this->assertSame(['default', 'hover', 'focus', 'focus-keyboard', 'filled', 'invalid', 'valid', 'disabled', 'readonly', 'loading'], FormStates::keys());

        foreach (FormStates::all() as $state) {
            $this->assertNotSame('', $state['label']);
            $this->assertNotSame('', $state['look']);
            $this->assertNotSame('', $state['hook']);
        }
    }
}
