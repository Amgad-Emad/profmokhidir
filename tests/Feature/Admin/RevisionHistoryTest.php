<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminUx;
use App\Constants\RevisionKind;
use App\Livewire\Admin\Awards\Form as AwardForm;
use App\Models\Award;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Version history on the edit screens: every save is kept, an earlier one
 * loads back into the form, and saving it records a restore.
 */
class RevisionHistoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_earlier_version_loads_into_the_form_and_is_kept_only_when_saved(): void
    {
        $editor = User::factory()->editor()->create(['name' => 'Amina']);
        $award = Award::factory()->draft()->create(['name' => 'First name', 'organisation' => 'Royal Society']);

        $component = Livewire::actingAs($editor)->test(AwardForm::class, ['award' => $award])
            ->call('save')
            ->set('form.name', 'Second name')
            ->call('save')
            ->assertSee('data-history-open', false)
            ->call('toggleHistory')
            ->assertSee('data-drawer="history"', false)
            ->assertSee('Version history')
            ->assertSee('Amina');

        $first = Revision::query()->orderBy('id')->firstOrFail();
        $this->assertSame('First name', $first->data['name']);

        $component->call('restoreRevision', $first->getKey())
            ->assertSet('form.name', 'First name')
            ->assertSet('showHistory', false)
            ->assertDispatched(AdminUx::EVENT_CHANGED);

        $this->assertSame('Second name', $award->fresh()->name);

        $component->call('save');

        $this->assertSame('First name', $award->fresh()->name);
        $this->assertSame(RevisionKind::Restore, Revision::query()->latest('id')->first()->kind);
    }

    #[Test]
    public function a_revision_of_another_record_cannot_be_loaded(): void
    {
        $award = Award::factory()->draft()->create(['name' => 'Mine']);
        $foreign = Revision::factory()->of(Award::factory()->create())->create(['data' => ['name' => 'Not mine']]);

        Livewire::actingAs(User::factory()->editor()->create())->test(AwardForm::class, ['award' => $award])
            ->call('restoreRevision', $foreign->getKey())
            ->assertSet('form.name', 'Mine');
    }
}
