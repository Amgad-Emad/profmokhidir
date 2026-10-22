<?php

namespace Tests\Feature\Admin;

use App\Constants\AuditAction;
use App\Constants\ContentStatus;
use App\Constants\CvSection;
use App\Constants\DateFormats;
use App\Livewire\Admin\Awards\Form as AwardForm;
use App\Livewire\Admin\CvEntries\Form as CvEntryForm;
use App\Livewire\Admin\Publications\Form as PublicationForm;
use App\Models\AuditLog;
use App\Models\Award;
use App\Models\CvEntry;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The shared create/edit form, exercised through awards, CV entries and publications.
 */
class ContentFormComponentTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->editor()->create();
    }

    #[Test]
    public function creating_validates_inline_saves_through_the_service_and_redirects_to_edit(): void
    {
        Livewire::actingAs($this->editor)->test(AwardForm::class)
            ->assertSet('form.status', ContentStatus::Draft->value)
            ->set('form.year', '1700')
            ->assertHasErrors(['form.year'])
            ->set('form.source_url', 'not a url')
            ->assertHasErrors(['form.source_url'])
            ->set('form.source_url', '')
            ->set('form.year', '1998')
            ->assertHasNoErrors(['form.year', 'form.source_url'])
            ->call('save')
            ->assertHasErrors(['form.name' => 'required'])
            ->set('form.name', 'Order of Merit')
            ->set('form.organisation', '  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.content.awards.edit', ['award' => Award::query()->first()->getKey()]));

        $award = Award::query()->first();
        $this->assertSame('Order of Merit', $award->name);
        $this->assertNull($award->organisation);
        $this->assertSame(1998, $award->year);
        $this->assertTrue($award->isDraft());
        $this->assertSame(1, $award->sort_order);
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CONTENT_CREATED)->where('user_id', $this->editor->getKey())->count());
    }

    #[Test]
    public function editing_updates_the_record_and_the_status_toggle_publishes_with_a_public_link(): void
    {
        $award = Award::factory()->draft()->create(['name' => 'Old name']);

        Livewire::actingAs($this->editor)->test(AwardForm::class, ['award' => $award])
            ->assertSet('form.name', 'Old name')
            ->assertDontSee('data-view-on-site', false)
            ->set('form.name', 'New name')
            ->set('form.status', ContentStatus::Published->value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Award saved.')
            ->assertSee('data-view-on-site', false)
            ->assertSee(route('awards'), false);

        $this->assertSame('New name', $award->fresh()->name);
        $this->assertTrue($award->fresh()->isPublished());
        $this->assertSame(1, AuditLog::query()->where('action', AuditAction::CONTENT_PUBLISHED)->count());
    }

    #[Test]
    public function cv_entries_check_the_year_range_and_offer_every_section(): void
    {
        Livewire::actingAs($this->editor)->test(CvEntryForm::class)
            ->assertSee('data-status-field', false)
            ->assertSee(CvSection::Education->label())
            ->set('form.section', 'education')
            ->set('form.title', 'BSc Agriculture')
            ->set('form.start_year', '1965')
            ->set('form.end_year', '1960')
            ->call('save')
            ->assertHasErrors(['form.end_year'])
            ->assertSee('Enter an end year no earlier than the start year.')
            ->set('form.end_year', '1969')
            ->call('save')
            ->assertHasNoErrors();

        $entry = CvEntry::query()->first();
        $this->assertSame(CvSection::Education, $entry->section);
        $this->assertSame('1965–1969', str_replace(DateFormats::RANGE_SEPARATOR, '–', (string) $entry->yearRange()));
    }

    #[Test]
    public function publication_slugs_must_be_unique_except_for_the_record_itself(): void
    {
        Publication::factory()->create(['slug' => 'taken']);
        $mine = Publication::factory()->create(['slug' => 'mine', 'title' => 'Mine']);

        Livewire::actingAs($this->editor)->test(PublicationForm::class, ['publication' => $mine])
            ->set('form.slug', 'taken')
            ->assertHasErrors(['form.slug'])
            ->set('form.slug', 'Not Valid')
            ->assertHasErrors(['form.slug'])
            ->assertSee('lowercase letters, numbers and hyphens')
            ->set('form.slug', 'mine')
            ->assertHasNoErrors(['form.slug'])
            ->set('form.doi', 'https://doi.org/10.1000/abc')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('10.1000/abc', $mine->fresh()->doi);
    }

    #[Test]
    public function members_cannot_open_the_form_and_trashed_records_are_not_editable(): void
    {
        Livewire::actingAs(User::factory()->member()->create())->test(AwardForm::class)->assertForbidden();

        $award = Award::factory()->create();
        $award->delete();

        $this->actingAs($this->editor)->get(route('admin.content.awards.edit', ['award' => $award->getKey()]))->assertNotFound();
    }
}
