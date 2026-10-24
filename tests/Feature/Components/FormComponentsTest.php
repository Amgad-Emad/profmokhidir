<?php

declare(strict_types=1);

namespace Tests\Feature\Components;

use App\Constants\AuthScreens;
use App\Constants\FormControls;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The text-like controls of resources/views/components/form: the field anatomy,
 * plain and Livewire bindings, errors, counters, year, date, password and code.
 */
class FormComponentsTest extends TestCase
{
    private function render(string $blade, array $errors = [], array $data = []): string
    {
        View::share('errors', (new ViewErrorBag)->put('default', new MessageBag($errors)));

        return Blade::render($blade, $data);
    }

    #[Test]
    public function a_field_reads_label_and_requirement_then_hint_then_control_then_error(): void
    {
        $html = $this->render('<x-form.input name="affiliation" label="Affiliation" required hint="Your university." />');

        $this->assertMatchesRegularExpression('/<label id="affiliation-label" for="affiliation" class="t-label form-label">Affiliation<\/label><span id="affiliation-requirement" class="form-requirement"><span aria-hidden="true">·<\/span> Required<\/span>\s*<p id="affiliation-hint" class="form-hint">Your university\.<\/p>\s*<div class="form-field__control"><div class="form-control"\s*>\s*<input class="form-control__input"/', $html);
        $this->assertStringContainsString('<p id="affiliation-error" class="form-message form-error" data-form-error></p>', $html, 'An empty error slot is ready for blur validation');
        $this->assertStringNotContainsString('*', strip_tags($html), 'Never a lone asterisk');
        $this->assertStringContainsString('placeholder="'.FormControls::BLANK_PLACEHOLDER.'"', $html);
    }

    #[Test]
    public function an_optional_field_says_so_and_a_filter_says_nothing(): void
    {
        $this->assertStringContainsString('<span id="location-requirement" class="form-requirement"><span aria-hidden="true">·</span> Optional</span>', $this->render('<x-form.input name="location" label="Place" />'));
        $this->assertStringNotContainsString('form-requirement', $this->render('<x-form.input name="q" label="Search" :optional="false" />'));
        $this->assertStringNotContainsString('form-hint', $this->render('<x-form.input name="q" label="Search" :optional="false" />'));
    }

    #[Test]
    public function an_error_marks_the_control_and_is_announced_under_it(): void
    {
        $html = $this->render('<x-form.input name="email" label="Email" type="email" required />', ['email' => FormControls::MESSAGE_EMAIL]);

        $this->assertStringContainsString('aria-invalid="true" aria-describedby="email-requirement email-error"', $html);
        $this->assertMatchesRegularExpression('/<p id="email-error" class="form-message form-error" data-form-error role="alert"><svg[^>]*>.*?<\/svg>\s*<span>'.preg_quote(FormControls::MESSAGE_EMAIL, '/').'<\/span><\/p>/s', $html);
    }

    #[Test]
    public function plain_inputs_keep_the_old_value_escaped(): void
    {
        $this->app['session.store']->flashInput(['name' => '"><script>x</script>']);
        $this->app['request']->setLaravelSession($this->app['session.store']);

        $html = $this->render('<x-form.input name="name" label="Name" />');

        $this->assertStringContainsString('value="&quot;&gt;&lt;script&gt;x&lt;/script&gt;"', $html);
        $this->assertStringNotContainsString('<script>x', $html);
    }

    #[Test]
    public function livewire_inputs_bind_on_blur_or_live_and_show_a_loading_state(): void
    {
        $html = $this->render('<x-form.input model="form.title" label="Title" required />');
        $this->assertStringContainsString('id="form-title" wire:model.live.blur="form.title" aria-required="true"', $html);
        $this->assertStringNotContainsString(' name=', $html);
        $this->assertStringNotContainsString('data-msg-', $html, 'Livewire fields are checked on the server');

        $search = $this->render('<x-form.input type="search" model="search" live label="Search" :optional="false" loading="search" />');
        $this->assertStringContainsString('wire:model.live.debounce.400ms="search"', $search);
        $this->assertStringContainsString('wire:loading.attr="data-loading" wire:target="search"', $search);
    }

    #[Test]
    public function other_attributes_reach_the_control_and_the_class_stays_on_the_field(): void
    {
        $html = $this->render('<x-form.input name="code" label="Code" inputmode="numeric" autocomplete="one-time-code" class="sm:col-span-2" />');

        $this->assertStringContainsString('<div class="form-field sm:col-span-2" data-form-field', $html);
        $this->assertStringContainsString('autocomplete="one-time-code"', $html);
        $this->assertStringContainsString('inputmode="numeric"', $html);
    }

    #[Test]
    public function a_textarea_grows_and_counts_towards_its_limit(): void
    {
        $html = $this->render('<x-form.textarea name="message" label="Your tribute" required :maxlength="600" rows="6" value="Thank you" />');

        $this->assertStringContainsString('maxlength="600" aria-describedby="message-requirement message-count"', $html);
        $this->assertStringContainsString('data-autogrow="true"', $html);
        $this->assertStringContainsString('rows="6"', $html);
        $this->assertStringContainsString('>Thank you</textarea>', $html);
        $this->assertStringContainsString('<p id="message-count" class="form-message form-counter" data-form-counter data-max="600" data-format="'.FormControls::COUNTER_FORMAT.'" aria-live="off">9 / 600 characters</p>', $html);
    }

    #[Test]
    public function a_select_is_native_with_its_placeholder_and_the_old_choice(): void
    {
        $html = $this->render('<x-form.select name="relationship" label="Relationship" :options="[\'student\' => \'Former student\', \'family\' => \'Family\']" placeholder="Choose one" value="family" required />');

        $this->assertStringContainsString('<div class="form-control form-control--select"', $html);
        $this->assertStringContainsString('<option value="">Choose one</option>', $html);
        $this->assertStringContainsString('<option value="family" selected>Family</option>', $html);
        $this->assertStringContainsString('data-msg-missing="Choose relationship."', $html);
    }

    #[Test]
    public function a_year_takes_four_digits_on_a_numeric_keypad_within_its_range(): void
    {
        $html = $this->render('<x-form.year name="year" label="Year" />');

        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('inputmode="numeric" pattern="[0-9]{4}"', $html);
        $this->assertStringContainsString('data-range-min="'.FormControls::YEAR_MIN.'" data-range-max="'.FormControls::yearMax().'"', $html);
        $this->assertStringContainsString('data-msg-range="'.FormControls::yearMessage().'"', $html);
        $this->assertStringContainsString('maxlength="4"', $html);

        $quoted = $this->render('<x-form.year name="y" label="Year it\'s from" hint="The field\'s own year." />');
        $this->assertStringContainsString('The field&#039;s own year.', $quoted, 'Wrappers escape their text once');
        $this->assertStringNotContainsString('&amp;#039;', $quoted);
        $this->assertStringNotContainsString('&amp;#039;', $this->render('<x-form.date name="d" label="Date it\'s from" hint="Day\'s date." />'));

        $bound = $this->render('<x-form.year model="form.year" label="Year" />');
        $this->assertStringContainsString('wire:model.live.blur="form.year"', $bound);
    }

    #[Test]
    public function a_date_uses_the_browsers_picker(): void
    {
        $html = $this->render('<x-form.date model="form.date" label="Date" />');

        $this->assertStringContainsString('wire:model.live.blur="form.date"', $html);
        $this->assertStringContainsString('type="date"', $html);
    }

    #[Test]
    public function a_password_has_show_hide_inside_the_frame_caps_lock_and_optional_strength(): void
    {
        $html = $this->render('<x-form.password name="password" label="New password" autocomplete="new-password" :strength="true" />');

        $this->assertStringContainsString('x-data="formPassword(', $html);
        $this->assertMatchesRegularExpression('/<input class="form-control__input"[^>]*type="password"[^>]*x-bind:type="type"[^>]*autocomplete="new-password"[^>]*>\s*<span class="form-control__status"[^>]*>.*?<\/span>\s*<button type="button" class="form-control__action"/s', $html, 'The toggle sits inside the frame, after the input');
        $this->assertStringContainsString('aria-label="'.FormControls::SHOW_PASSWORD.'" aria-controls="password"', $html);
        $this->assertStringContainsString('x-text="capsWarning" data-caps-warning', $html);
        $this->assertStringContainsString(AuthScreens::PASSWORD_HINT, $html);
        $this->assertStringContainsString('<div class="form-strength" data-password-strength>', $html);

        $this->assertStringNotContainsString('data-password-strength', $this->render('<x-form.password name="password_confirmation" label="Confirm" />'));
    }

    #[Test]
    public function a_one_time_code_is_six_framed_boxes_feeding_one_hidden_field(): void
    {
        $html = $this->render('<x-form.otp name="code" label="Authentication code" :hidden="[\'x-bind:disabled\' => \'recovery\']" />', ['code' => 'The code is not valid.']);

        $this->assertStringContainsString('<fieldset class="form-field form-fieldset" x-data="formOtp({ length: 6 })" data-otp="data-otp" data-form-field aria-describedby="code-requirement code-error"', $html);
        $this->assertSame(AuthScreens::CODE_LENGTH, substr_count($html, '<div class="form-control"><input class="form-control__input" type="text" id="code-'));
        $this->assertStringContainsString('<input type="hidden" name="code" x-ref="value" value="" x-bind:disabled="recovery" data-msg-missing="Enter the 6-digit code from your app.">', $html);
        $this->assertSame(AuthScreens::CODE_LENGTH, substr_count($html, 'aria-invalid="true"'));
    }
}
