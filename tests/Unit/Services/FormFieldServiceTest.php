<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Constants\FormControls;
use App\Services\FormFieldService;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FormFieldServiceTest extends TestCase
{
    private FormFieldService $forms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->forms = new FormFieldService;
    }

    private function errors(array $messages, string $bag = 'default'): ViewErrorBag
    {
        return (new ViewErrorBag)->put($bag, new MessageBag($messages));
    }

    #[Test]
    public function a_plain_field_is_described_by_its_requirement_hint_and_counter(): void
    {
        $field = $this->forms->describe(['name' => 'message', 'label' => 'Your tribute', 'hint' => 'A memory or a lesson.', 'required' => true, 'maxlength' => 600]);

        $this->assertSame('message', $field['id']);
        $this->assertSame('message', $field['key']);
        $this->assertSame(FormControls::REQUIRED, $field['requirement']);
        $this->assertSame('A memory or a lesson.', $field['hint'], 'The requirement sits beside the label, not in the hint');
        $this->assertSame('A memory or a lesson.', $field['hintText']);
        $this->assertTrue($field['counter']);
        $this->assertSame('message-requirement', $field['requirementId']);
        $this->assertSame('message-requirement message-hint message-count', $field['describedBy']);
        $this->assertFalse($field['invalid']);
    }

    #[Test]
    public function a_field_that_is_not_required_says_optional_unless_it_opts_out(): void
    {
        $this->assertSame(FormControls::OPTIONAL, $this->forms->describe(['name' => 'location', 'label' => 'Place'])['requirement']);
        $this->assertSame('', $this->forms->describe(['name' => 'location', 'label' => 'Place'])['hint']);
        $this->assertNull($this->forms->describe(['name' => 'q', 'label' => 'Search', 'optional' => false])['requirement']);
        $this->assertSame('', $this->forms->describe(['name' => 'q', 'label' => 'Search', 'optional' => false])['describedBy']);
        $this->assertNull($this->forms->describe(['name' => 'remember', 'label' => 'Remember', 'required' => true, 'marker' => false])['requirement']);
    }

    #[Test]
    public function livewire_ids_follow_the_bound_property_and_errors_come_from_its_key(): void
    {
        $field = $this->forms->describe(['model' => 'form.key_facts.0.label', 'label' => 'Label'], $this->errors(['form.key_facts.0.label' => 'Enter a label.']));

        $this->assertSame('form-key-facts-0-label', $field['id']);
        $this->assertSame('Enter a label.', $field['error']);
        $this->assertTrue($field['invalid']);
        $this->assertStringEndsWith('form-key-facts-0-label-error', $field['describedBy']);
    }

    #[Test]
    public function list_controls_also_show_the_messages_of_their_items(): void
    {
        $errors = $this->errors(['form.expertise.2' => 'Shorten this to 120 characters or fewer.']);

        $this->assertNull($this->forms->describe(['model' => 'form.expertise', 'label' => 'Expertise'], $errors)['error']);
        $this->assertSame('Shorten this to 120 characters or fewer.', $this->forms->describe(['model' => 'form.expertise', 'label' => 'Expertise', 'nested' => true], $errors)['error']);
    }

    #[Test]
    public function errors_are_read_from_the_named_bag_and_an_explicit_error_wins(): void
    {
        $errors = $this->errors(['password' => 'Enter your current password as you last set it.'], 'updatePassword');

        $this->assertNull($this->forms->describe(['name' => 'password', 'label' => 'Password'], $errors)['error']);
        $this->assertSame('Enter your current password as you last set it.', $this->forms->describe(['name' => 'password', 'label' => 'Password', 'bag' => 'updatePassword'], $errors)['error']);
        $this->assertSame('Shown on the style guide.', $this->forms->describe(['name' => 'year', 'label' => 'Year', 'error' => 'Shown on the style guide.'])['error']);
        $this->assertNull($this->forms->describe(['name' => 'year', 'label' => 'Year'], null)['error'], 'Error pages render without an error bag');
    }

    #[Test]
    public function plain_controls_get_a_name_and_native_required_and_livewire_controls_get_wire_model(): void
    {
        $plain = (string) $this->forms->controlAttributes($this->forms->describe(['name' => 'email', 'label' => 'Email', 'required' => true]), ['type' => 'email']);
        $this->assertSame('id="email" name="email" required="required" aria-describedby="email-requirement" data-label="email" type="email"', $plain);

        $bound = (string) $this->forms->controlAttributes($this->forms->describe(['model' => 'form.title', 'wire' => 'live.blur', 'label' => 'Title', 'required' => true]));
        $this->assertSame('id="form-title" wire:model.live.blur="form.title" aria-required="true" aria-describedby="form-title-requirement" data-label="title"', $bound);

        $live = (string) $this->forms->controlAttributes($this->forms->describe(['model' => 'search', 'wire' => 'live.debounce.400ms', 'label' => 'Search', 'optional' => false]));
        $this->assertStringContainsString('wire:model.live.debounce.400ms="search"', $live);
    }

    #[Test]
    public function values_from_props_are_escaped_and_attributes_blade_escaped_are_kept_as_they_are(): void
    {
        $field = $this->forms->describe(['name' => 'q', 'label' => 'Search', 'optional' => false]);
        $html = (string) $this->forms->controlAttributes($field, ['value' => '<script>alert(1)</script>'], ['placeholder' => 'Soil &amp; water']);

        $this->assertStringContainsString('value="&lt;script&gt;alert(1)&lt;/script&gt;"', $html);
        $this->assertStringContainsString('placeholder="Soil &amp; water"', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    #[Test]
    public function extra_described_by_ids_are_joined_not_replaced(): void
    {
        $field = $this->forms->describe(['name' => 'body', 'label' => 'Message', 'optional' => false], $this->errors(['body' => 'Write a message first.']));
        $html = (string) $this->forms->controlAttributes($field, ['aria-describedby' => 'composer-hint']);

        $this->assertStringContainsString('aria-describedby="body-error composer-hint"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
    }

    #[Test]
    public function browser_messages_name_the_fix_for_plain_forms_only(): void
    {
        $email = $this->forms->describe(['name' => 'email', 'label' => 'Email', 'required' => true]);
        $this->assertSame([
            'data-msg-missing' => 'Enter email.',
            'data-msg-type' => FormControls::MESSAGE_EMAIL,
        ], $this->forms->browserMessages($email, 'email'));

        $year = $this->forms->describe(['name' => 'year', 'label' => 'Year', 'maxlength' => 4]);
        $this->assertSame('Enter a year between 1900 and 2100.', $this->forms->browserMessages($year, 'year', ['min' => 1900, 'max' => 2100])['data-msg-range']);
        $this->assertSame('Shorten this to 4 characters or fewer.', $this->forms->browserMessages($year, 'year')['data-msg-long']);

        $agree = $this->forms->describe(['name' => 'consent', 'label' => 'Consent', 'required' => true]);
        $this->assertSame(FormControls::MESSAGE_CHECK, $this->forms->browserMessages($agree, 'check')['data-msg-missing']);

        $choice = $this->forms->describe(['name' => 'relationship', 'label' => 'Relationship', 'required' => true]);
        $this->assertSame('Choose relationship.', $this->forms->browserMessages($choice, 'select')['data-msg-missing']);

        $bound = $this->forms->describe(['model' => 'form.email', 'label' => 'Email', 'required' => true]);
        $this->assertSame([], $this->forms->browserMessages($bound, 'email'), 'Livewire fields are checked on the server');
    }

    #[Test]
    public function names_become_ids_and_error_keys(): void
    {
        $this->assertSame('tags', $this->forms->idFor(null, 'tags[]'));
        $this->assertSame('links-0-url', $this->forms->idFor(null, 'links[0][url]'));
        $this->assertSame('form-start-year', $this->forms->idFor('form.start_year', 'ignored'));
        $this->assertSame('links.0.url', $this->forms->errorKey('links[0][url]'));
        $this->assertSame('tags', $this->forms->errorKey('tags[]'));
    }
}
