<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminUx;
use App\Constants\AuditAction;
use App\Constants\ContentStatus;
use App\Constants\Placeholders;
use App\Constants\RevisionKind;
use App\Livewire\Admin\Awards\Form as AwardForm;
use App\Livewire\Admin\Profile\EditProfile;
use App\Models\AuditLog;
use App\Models\Award;
use App\Models\Profile;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The edit screen every content form shares: the save bar's Save draft,
 * Publish, Update and Unpublish, autosave of drafts, the preview link and
 * the placeholder notice.
 */
class EditScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->editor()->create();
    }

    #[Test]
    public function a_draft_shows_save_draft_publish_and_preview_and_publishing_records_a_revision(): void
    {
        $award = Award::factory()->draft()->create(['name' => 'Gold medal']);

        Livewire::actingAs($this->editor)->test(AwardForm::class, ['award' => $award])
            ->assertSee('data-save-bar', false)
            ->assertSee('data-shortcut="save"', false)
            ->assertSee('data-shortcut="publish"', false)
            ->assertSee('data-preview', false)
            ->assertSee('preview=award.'.$award->getKey(), false)
            ->assertDontSee('data-unpublish', false)
            ->call('publish')
            ->assertHasNoErrors()
            ->assertDispatched(AdminUx::EVENT_TOAST, fn (string $name, array $params): bool => $params['message'] === 'Award published. It is on the public site now.')
            ->assertDispatched(AdminUx::EVENT_SAVED)
            ->assertSee('data-unpublish', false)
            ->assertSee('Update')
            ->assertSee('data-view-on-site', false);

        $this->assertTrue($award->fresh()->isPublished());
        $this->assertSame(RevisionKind::Publish, Revision::query()->latest('id')->first()->kind);
    }

    #[Test]
    public function unpublishing_keeps_the_edits_and_takes_the_record_off_the_site(): void
    {
        $award = Award::factory()->published()->create(['name' => 'Gold medal']);

        Livewire::actingAs($this->editor)->test(AwardForm::class, ['award' => $award])
            ->set('form.name', 'Gold medal of merit')
            ->call('unpublish')
            ->assertHasNoErrors()
            ->assertSee('Award moved back to draft. Visitors no longer see it.');

        $this->assertTrue($award->fresh()->isDraft());
        $this->assertSame('Gold medal of merit', $award->fresh()->name);
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CONTENT_UNPUBLISHED)->count());
    }

    #[Test]
    public function a_failed_publish_keeps_the_draft_status_and_says_why(): void
    {
        $award = Award::factory()->draft()->create(['name' => 'Gold medal']);

        Livewire::actingAs($this->editor)->test(AwardForm::class, ['award' => $award])
            ->set('form.name', '')
            ->call('publish')
            ->assertHasErrors(['form.name'])
            ->assertSet('form.status', ContentStatus::Draft->value)
            ->assertDispatched(AdminUx::EVENT_TOAST, fn (string $name, array $params): bool => $params['tone'] === AdminUx::TOAST_ERROR);

        $this->assertTrue($award->fresh()->isDraft());
    }

    #[Test]
    public function autosave_quietly_saves_a_valid_draft_and_records_an_autosave(): void
    {
        $award = Award::factory()->draft()->create(['name' => 'Gold medal']);

        Livewire::actingAs($this->editor)->test(AwardForm::class, ['award' => $award])
            ->set('form.name', 'Gold medal, 1990')
            ->call('autosave')
            ->assertHasNoErrors()
            ->assertNotDispatched(AdminUx::EVENT_TOAST)
            ->assertDispatched(AdminUx::EVENT_SAVED, fn (string $name, array $params): bool => isset($params['at']));

        $this->assertSame('Gold medal, 1990', $award->fresh()->name);
        $this->assertTrue($award->fresh()->isDraft());
        $this->assertSame(RevisionKind::Autosave, Revision::query()->latest('id')->first()->kind);
    }

    #[Test]
    public function autosave_waits_on_invalid_fields_without_marking_them_and_never_touches_a_published_record(): void
    {
        $draft = Award::factory()->draft()->create(['name' => 'Gold medal']);

        Livewire::actingAs($this->editor)->test(AwardForm::class, ['award' => $draft])
            ->set('form.name', '')
            ->call('autosave')
            ->assertDispatched(AdminUx::EVENT_SAVED, fn (string $name, array $params): bool => str_starts_with($params['blocked'] ?? '', 'Not saved yet:'));

        $this->assertSame('Gold medal', $draft->fresh()->name);

        $published = Award::factory()->published()->create(['name' => 'Silver']);

        Livewire::actingAs($this->editor)->test(AwardForm::class, ['award' => $published])
            ->set('form.name', 'Silver, edited')
            ->call('autosave')
            ->assertDispatched(AdminUx::EVENT_SAVED, fn (string $name, array $params): bool => ($params['autosave'] ?? null) === false);

        $this->assertSame('Silver', $published->fresh()->name);
    }

    #[Test]
    public function autosaving_a_new_record_creates_a_draft_and_moves_the_address_to_its_edit_screen(): void
    {
        $component = Livewire::actingAs($this->editor)->test(AwardForm::class)
            ->set('form.name', 'Bronze cup')
            ->set('form.status', ContentStatus::Published->value)
            ->call('autosave');

        $award = Award::query()->firstOrFail();
        $component->assertDispatched(AdminUx::EVENT_ADDRESS, url: route('admin.content.awards.edit', ['award' => $award->getKey()]), crumb: 'Bronze cup')
            ->assertSet('recordId', $award->getKey())
            ->assertSet('wasEditing', true)
            ->set('form.name', 'Bronze cup, 1975')
            ->call('autosave')
            ->assertNotDispatched(AdminUx::EVENT_ADDRESS);

        $this->assertTrue($award->isDraft());
        $this->assertSame(1, Award::query()->count());
    }

    #[Test]
    public function the_first_manual_save_of_a_new_record_opens_its_edit_screen(): void
    {
        Livewire::actingAs($this->editor)->test(AwardForm::class)
            ->set('form.name', 'Bronze cup')
            ->call('save')
            ->assertRedirect(route('admin.content.awards.edit', ['award' => Award::query()->firstOrFail()->getKey()]));
    }

    #[Test]
    public function fields_still_holding_placeholder_copy_are_summarised(): void
    {
        $award = Award::factory()->draft()->create(['name' => Placeholders::MARKER.' Prize', 'description' => Placeholders::MARKER.' text', 'organisation' => 'Royal Society']);

        Livewire::actingAs($this->editor)->test(AwardForm::class, ['award' => $award])
            ->assertSee('data-placeholder-summary', false)
            ->assertSee('2 fields on this page still hold sample text')
            ->set('form.name', 'Prize')
            ->set('form.description', 'Real text')
            ->assertDontSee('data-placeholder-summary', false);
    }

    #[Test]
    public function the_profile_is_split_into_named_sections_with_its_own_save(): void
    {
        $profile = Profile::factory()->draft()->create(['name' => 'Mohamed Osman Khidir']);

        Livewire::actingAs($this->editor)->test(EditProfile::class)
            ->assertSeeInOrder(['href="#section-identity"', 'href="#section-biography"', 'href="#section-expertise"', 'href="#section-portrait"', 'href="#section-contact"'], false)
            ->assertSeeInOrder(['id="section-identity"', 'id="section-biography"', 'id="section-expertise"', 'id="section-portrait"', 'id="section-contact"'], false)
            ->set('form.affiliation', 'University of Khartoum')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Profile saved.')
            ->assertNoRedirect();

        $this->assertSame('University of Khartoum', $profile->fresh()->affiliation);
    }
}
