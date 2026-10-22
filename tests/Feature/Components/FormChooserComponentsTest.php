<?php

declare(strict_types=1);

namespace Tests\Feature\Components;

use App\Constants\ContentStatus;
use App\Constants\FormControls;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The choosing and composite controls of resources/views/components/form:
 * checkbox, radio group, toggle, combobox, tags, upload, editor and the summary.
 */
class FormChooserComponentsTest extends TestCase
{
    private function render(string $blade, array $errors = [], array $data = []): string
    {
        View::share('errors', (new ViewErrorBag)->put('default', new MessageBag($errors)));

        return Blade::render($blade, $data);
    }

    #[Test]
    public function a_checkbox_is_a_styled_real_input_with_its_label_and_never_says_optional(): void
    {
        $html = $this->render('<x-form.checkbox name="consent" required>I agree.</x-form.checkbox>');

        $this->assertStringContainsString('<label class="form-check" for="consent">', $html);
        $this->assertStringContainsString('<input class="form-check__box" id="consent" name="consent" required="required" type="checkbox" value="1" data-msg-missing="'.FormControls::MESSAGE_CHECK.'">', $html);
        $this->assertStringContainsString('<span class="form-check__text">I agree.</span>', $html);
        $this->assertStringNotContainsString(FormControls::OPTIONAL, $html);
        $this->assertStringNotContainsString('consent-hint', $html);
    }

    #[Test]
    public function a_bound_checkbox_keeps_its_value_for_array_properties(): void
    {
        $html = $this->render('<x-form.checkbox model="selected" live id="select-7" value="7"><span class="sr-only">Select</span></x-form.checkbox>');

        $this->assertStringContainsString('id="select-7" wire:model.live="selected" type="checkbox" value="7"', $html);
    }

    #[Test]
    public function a_radio_group_is_a_fieldset_with_a_legend_and_an_empty_option_keeps_its_value(): void
    {
        $html = $this->render('<x-form.radio-group name="type" label="Type" :optional="false" value="" :options="[\'\' => \'All types\', \'book\' => \'Book\']" />');

        $this->assertStringContainsString('<fieldset class="form-field form-fieldset" role="radiogroup" data-form-field>', $html);
        $this->assertStringContainsString('<legend id="type-label" class="t-label form-label">Type</legend>', $html);
        $this->assertStringContainsString('<input class="form-check__box" type="radio" id="type-any" name="type" value="" checked="checked">', $html);
        $this->assertStringContainsString('<input class="form-check__box" type="radio" id="type-book" name="type" value="book">', $html);
    }

    #[Test]
    public function the_segmented_variant_binds_live_and_shows_icons(): void
    {
        $html = $this->render('<x-form.radio-group model="form.status" label="Status" :options="$options" :icons="$icons" variant="segmented" required data-status-field />', [], [
            'options' => ContentStatus::labels(),
            'icons' => ['draft' => 'clock', 'published' => 'check'],
        ]);

        $this->assertStringContainsString('<div class="form-segmented" data-status-field="data-status-field">', $html);
        $this->assertSame(2, substr_count($html, 'wire:model.live="form.status"'));
        $this->assertSame(2, substr_count($html, '<label class="form-segmented__option"'));
        $this->assertStringContainsString('<span class="form-hint__requirement">Required</span>', $html);
    }

    #[Test]
    public function a_toggle_is_a_switch_with_its_state_in_words(): void
    {
        $html = $this->render('<x-form.toggle model="archive.4.featured" label="Featured on the wall" hint="Hangs one size larger." />');

        $this->assertStringContainsString('role="switch"', $html);
        $this->assertStringContainsString('wire:model="archive.4.featured"', $html);
        $this->assertStringContainsString('aria-describedby="archive-4-featured-note"', $html);
        $this->assertStringContainsString('<span class="form-toggle__on">'.FormControls::TOGGLE_ON.'</span><span class="form-toggle__off">'.FormControls::TOGGLE_OFF.'</span>', $html);

        $plain = $this->render('<x-form.toggle name="featured" label="Featured" :checked="true" />');
        $this->assertStringContainsString('<input type="hidden" name="featured" value="0">', $plain, 'Off is sent too');
        $this->assertStringContainsString('checked="checked"', $plain);
    }

    #[Test]
    public function a_combobox_is_an_aria_combobox_over_a_listbox_with_a_native_fallback(): void
    {
        $html = $this->render('<x-form.combobox model="uploadAlbumId" label="Into album" :options="[3 => \'Khartoum\', 4 => \'Field seasons\']" required />');

        $this->assertStringContainsString('role="combobox"', $html);
        $this->assertStringContainsString('aria-autocomplete="list" aria-controls="uploadAlbumId-listbox" aria-expanded="false"', $html);
        $this->assertStringContainsString('x-modelable="value" wire:model.live="uploadAlbumId"', $html);
        $this->assertStringContainsString('<ul id="uploadAlbumId-listbox" class="form-listbox" role="listbox" aria-labelledby="uploadAlbumId-label"', $html);
        $this->assertStringContainsString('\\u0022label\\u0022:\\u0022Field seasons\\u0022', $html);
        $this->assertStringContainsString('<noscript>', $html);
        $this->assertStringContainsString(FormControls::COMBOBOX_NO_MATCHES, $html);
    }

    #[Test]
    public function tags_bind_an_array_or_a_joined_string_and_show_item_errors(): void
    {
        $bound = $this->render('<x-form.tags model="form.expertise" label="Expertise" />', ['form.expertise.1' => 'Shorten this to 120 characters or fewer.']);
        $this->assertStringContainsString('x-modelable="value" wire:model="form.expertise"', $bound);
        $this->assertStringContainsString('Shorten this to 120 characters or fewer.', $bound);
        $this->assertStringContainsString(FormControls::TAGS_ADD_HINT, $bound);

        $plain = $this->render('<x-form.tags name="topics" label="Topics" separator="," :value="[\'Soil\', \'Water\']" :suggestions="[\'Irrigation\']" />');
        $this->assertStringContainsString('<input type="hidden" name="topics" x-bind:value="tags.join(', $plain);
        $this->assertStringContainsString('value="Soil, Water"', $plain);
        $this->assertStringContainsString('<datalist id="topics-suggestions">', $plain);
        $this->assertStringContainsString('list="topics-suggestions"', $plain);
    }

    #[Test]
    public function an_upload_is_a_drop_zone_with_progress_replace_remove_and_an_alt_prompt(): void
    {
        $html = $this->render('<x-form.upload model="photo" label="Portrait" image alt="details.alt_text" :alt-required="true" accept="image/*" remove="removePhoto" />');

        $this->assertStringContainsString('<input class="form-drop__input" id="photo" wire:model="photo" aria-describedby="photo-hint" type="file" accept="image/*"', $html);
        $this->assertStringContainsString('x-on:livewire-upload-progress="progress($event.detail.progress)"', $html);
        $this->assertStringContainsString('class="form-progress"', $html);
        $this->assertStringContainsString(FormControls::UPLOAD_REPLACE, $html);
        $this->assertStringContainsString('wire:click="removePhoto"', $html);
        $this->assertStringContainsString('data-alt-prompt', $html);
        $this->assertStringContainsString('wire:model.live.blur="details.alt_text"', $html);
        $this->assertStringContainsString(FormControls::UPLOAD_ALT_PROMPT, $html);

        $this->assertStringNotContainsString('livewire', $this->render('<x-form.upload name="file" label="File" />'), 'Plain forms carry no Livewire hooks');
    }

    #[Test]
    public function the_editor_has_a_labelled_toolbar_with_shortcuts_a_link_dialog_and_a_word_count(): void
    {
        $html = $this->render('<x-form.editor model="form.body" label="Body" />');

        $this->assertStringContainsString('role="toolbar" aria-label="'.FormControls::EDITOR_TOOLBAR.'" aria-controls="form-body-editor"', $html);

        foreach (FormControls::editorTools() as $command => $tool) {
            $this->assertStringContainsString('data-tool="'.$command.'"', $html);
            $this->assertStringContainsString('aria-label="'.$tool['label'].'"', $html);
            $this->assertStringContainsString('<kbd data-shortcut>'.FormControls::shortcutLabel($tool['shortcut']).'</kbd>', $html);
        }

        $this->assertSame(1, substr_count($html, 'tabindex="0"'), 'The toolbar is one Tab stop');
        $this->assertStringContainsString('aria-keyshortcuts="Control+B"', $html);
        $this->assertStringContainsString('role="dialog" aria-modal="false" aria-labelledby="form-body-link-title"', $html);
        $this->assertStringContainsString('type="url"', $html);
        $this->assertStringContainsString('data-word-count', $html);
        $this->assertStringContainsString('wire:ignore', $html);
        $this->assertStringContainsString('<div class="form-control form-control--editor"', $html, 'One frame around toolbar and text');
        $this->assertStringNotContainsString('rich-text__', $html);
    }

    #[Test]
    public function the_summary_links_each_message_to_its_field_and_waits_for_a_failed_save_in_livewire(): void
    {
        $plain = $this->render('<x-form.error-summary :targets="[\'code\' => \'code-1\']" />', ['name' => 'Enter your name.', 'code' => 'Enter the code.']);
        $this->assertStringContainsString('role="alert" tabindex="-1" data-error-summary x-data x-init="$el.focus()">', $plain);
        $this->assertStringContainsString('<a href="#name">Enter your name.</a>', $plain);
        $this->assertStringContainsString('<a href="#code-1">Enter the code.</a>', $plain);

        $bound = $this->render('<x-form.error-summary after-save />', ['form.start_year' => FormControls::yearMessage()]);
        $this->assertStringContainsString('x-show="shown" x-cloak x-on:form-invalid.window="shown = true"', $bound);
        $this->assertStringContainsString('<a href="#form-start-year">', $bound);

        $this->assertStringContainsString('data-error-summary hidden>', $this->render('<x-form.error-summary />'), 'An empty shell the script can fill');
        $this->assertStringNotContainsString('<a href="#limit">', $this->render('<x-form.error-summary :except="[\'limit\']" />', ['limit' => 'Too many.', 'name' => 'Enter your name.']));
    }
}
