<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Constants\FormControls;
use App\Livewire\Admin\Awards\Form as AwardForm;
use App\Livewire\Admin\Commentaries\Form as CommentaryForm;
use App\Livewire\Admin\CvEntries\Form as CvEntryForm;
use App\Livewire\Admin\Profile\EditProfile;
use App\Models\Commentary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The dashboard forms use the shared controls through wire:model bindings: fields
 * validate as they are left, messages name the fix and the summary waits for a save.
 */
class AdminFormControlsTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->editor()->create();
    }

    #[Test]
    public function the_award_form_binds_every_control_and_opens_with_a_summary_that_waits_for_a_save(): void
    {
        Livewire::actingAs($this->editor)->test(AwardForm::class)
            ->assertSeeHtml('wire:model.live.blur="form.name" aria-required="true"')
            ->assertSeeHtml('<span aria-hidden="true">·</span> '.FormControls::REQUIRED.'</span>')
            ->assertSeeHtml('<span aria-hidden="true">·</span> '.FormControls::OPTIONAL.'</span>')
            ->assertSeeHtml('wire:model.live.blur="form.year"')
            ->assertSeeHtml('inputmode="numeric" pattern="[0-9]{4}"')
            ->assertSeeHtml('wire:click="save"')
            ->assertSeeHtml('wire:click="publish"')
            ->assertSeeHtml('x-on:form-invalid.window="shown = true"')
            ->assertDontSeeHtml('<span aria-hidden="true">*</span>');
    }

    #[Test]
    public function a_year_out_of_range_is_caught_when_the_field_is_left_with_a_message_naming_the_range(): void
    {
        Livewire::actingAs($this->editor)->test(AwardForm::class)
            ->set('form.year', '1850')
            ->assertHasErrors(['form.year'])
            ->assertSeeHtml('aria-invalid="true"')
            ->assertSee(FormControls::yearMessage())
            ->assertSeeHtml('<a href="#form-year">'.FormControls::yearMessage().'</a>')
            ->set('form.year', '1984')
            ->assertHasNoErrors(['form.year'])
            ->assertDontSee(FormControls::yearMessage());
    }

    #[Test]
    public function the_cv_form_explains_an_end_year_before_the_start(): void
    {
        Livewire::actingAs($this->editor)->test(CvEntryForm::class)
            ->set('form.start_year', '1990')
            ->set('form.end_year', '1980')
            ->assertSee('Enter an end year no earlier than the start year.');
    }

    #[Test]
    public function the_profile_uses_tags_for_expertise_and_the_editor_for_the_biography(): void
    {
        Livewire::actingAs($this->editor)->test(EditProfile::class)
            ->assertSeeHtml('x-modelable="value" wire:model="form.expertise"')
            ->assertSeeHtml('data-expertise')
            ->assertSeeHtml('x-data="formEditor(')
            ->assertSeeHtml('data-tool="blockquote"')
            ->assertSeeHtml('aria-label="Quote"')
            ->assertSeeHtml('data-tool="link"')
            ->assertSeeHtml('<kbd data-shortcut>Ctrl+K</kbd>')
            ->set('form.expertise', ['Agronomy', 'Soil science'])
            ->assertSet('form.expertise', ['Agronomy', 'Soil science']);
    }

    #[Test]
    public function commentary_topics_are_tags_offering_the_topics_in_use(): void
    {
        Commentary::factory()->published()->create(['tags' => ['Irrigation', 'Teaching']]);

        Livewire::actingAs($this->editor)->test(CommentaryForm::class)
            ->assertSeeHtml('x-modelable="value" wire:model="form.tags"')
            ->assertSeeHtml('<option value="Irrigation"></option>')
            ->assertSeeHtml('<option value="Teaching"></option>');
    }
}
