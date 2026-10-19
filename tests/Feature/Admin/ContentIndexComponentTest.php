<?php

namespace Tests\Feature\Admin;

use App\Constants\AuditAction;
use App\Constants\ContentStatus;
use App\Constants\PublicationType;
use App\Livewire\Admin\Awards\Index as AwardIndex;
use App\Livewire\Admin\Publications\Index as PublicationIndex;
use App\Models\AuditLog;
use App\Models\Award;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The shared index table, exercised through awards and publications.
 */
class ContentIndexComponentTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->editor()->create();
    }

    #[Test]
    public function the_table_searches_filters_and_sorts(): void
    {
        Award::factory()->published()->sortOrder(1)->create(['name' => 'Gold medal', 'year' => 1990]);
        Award::factory()->draft()->sortOrder(2)->create(['name' => 'Silver medal', 'year' => 2005]);
        Award::factory()->published()->sortOrder(3)->create(['name' => 'Bronze cup', 'year' => 2005]);

        Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertSeeInOrder(['Gold medal', 'Silver medal', 'Bronze cup'])
            ->assertSee('data-filter="year"', false)
            ->assertSee('data-filter="status"', false)
            ->set('search', 'medal')
            ->assertSee('Gold medal')->assertSee('Silver medal')->assertDontSee('Bronze cup')
            ->assertSee('Clear the search, filters and sorting')
            ->set('search', '')
            ->set('filters.status', ContentStatus::Published->value)
            ->assertSee('Gold medal')->assertDontSee('Silver medal')->assertSee('Bronze cup')
            ->set('filters.status', '')
            ->set('filters.year', '2005')
            ->assertDontSee('Gold medal')->assertSee('Silver medal')->assertSee('Bronze cup')
            ->set('filters.year', '')
            ->call('sortBy', 'name')
            ->assertSet('sort', 'name')->assertSet('direction', 'asc')
            ->assertSeeInOrder(['Bronze cup', 'Gold medal', 'Silver medal'])
            ->assertSee('aria-sort="ascending"', false)
            ->call('sortBy', 'name')
            ->assertSet('direction', 'desc')
            ->assertSeeInOrder(['Silver medal', 'Gold medal', 'Bronze cup'])
            ->call('sortBy', 'password')
            ->assertSet('sort', 'name')
            ->call('clearSort')
            ->assertSet('sort', '')
            ->assertSee('data-move-buttons', false);
    }

    #[Test]
    public function publish_and_unpublish_from_the_table_are_audited_and_show_the_public_link(): void
    {
        $award = Award::factory()->draft()->create(['name' => 'Gold medal']);

        Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertDontSee('data-view-on-site', false)
            ->call('publish', $award->getKey())
            ->assertSee('Award published.')
            ->assertSee('data-view-on-site', false)
            ->assertSee(route('awards'), false)
            ->call('unpublish', $award->getKey())
            ->assertSee('moved back to draft');

        $this->assertSame([AuditAction::CONTENT_PUBLISHED, AuditAction::CONTENT_UNPUBLISHED], AuditLog::query()->orderBy('id')->pluck('action')->all());
        $this->assertTrue($award->fresh()->isDraft());
    }

    #[Test]
    public function deleting_needs_confirmation_moves_to_the_trash_and_can_be_restored(): void
    {
        $award = Award::factory()->published()->create(['name' => 'Gold medal']);

        $component = Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertDontSee('data-confirm-dialog', false)
            ->call('delete') // nothing confirmed yet
            ->call('confirm', 'delete', $award->getKey())
            ->assertSee('data-confirm-dialog', false)
            ->assertSee('Move to the trash?')
            ->call('cancelConfirmation')
            ->assertDontSee('data-confirm-dialog', false);

        $this->assertNull($award->fresh()->deleted_at);

        $component->call('confirm', 'delete', $award->getKey())
            ->call('delete')
            ->assertSee('moved to the trash')
            ->assertDontSee('Gold medal')
            ->assertSee('Trash<span class="ml-2 font-mono text-xs text-ash">1</span>', false)
            ->call('showTrash')
            ->assertSet('view', 'trash')
            ->assertSee('Gold medal')
            ->assertSee('data-restore', false)
            ->call('restore', $award->getKey())
            ->assertSee('Award restored.')
            ->assertSee('The trash is empty.')
            ->call('showActive')
            ->assertSee('Gold medal');

        $this->assertNull($award->fresh()->deleted_at);
        $this->assertSame([AuditAction::CONTENT_DELETED, AuditAction::CONTENT_RESTORED], AuditLog::query()->orderBy('id')->pluck('action')->all());
    }

    #[Test]
    public function rows_can_be_moved_with_buttons_or_reordered_by_drag_and_drop(): void
    {
        $a = Award::factory()->sortOrder(1)->create(['name' => 'A']);
        $b = Award::factory()->sortOrder(2)->create(['name' => 'B']);
        $c = Award::factory()->sortOrder(3)->create(['name' => 'C']);

        $component = Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertSee('draggable="true"', false)
            ->call('moveDown', $a->getKey())
            ->assertSeeInOrder(['>B<', '>A<', '>C<'])
            ->call('moveUp', $c->getKey())
            ->assertSeeInOrder(['>B<', '>C<', '>A<'])
            ->call('reorder', [$a->getKey(), $b->getKey(), $c->getKey()])
            ->assertSee('Order saved.')
            ->assertSeeInOrder(['>A<', '>B<', '>C<']);

        $this->assertSame(['A', 'B', 'C'], Award::query()->ordered()->pluck('name')->all());

        // Reordering is refused while the list is searched or sorted.
        $component->set('search', 'A')
            ->assertDontSee('draggable="true"', false)
            ->call('reorder', [$c->getKey(), $b->getKey(), $a->getKey()])
            ->assertDontSee('Order saved.');

        $this->assertSame(['A', 'B', 'C'], Award::query()->ordered()->pluck('name')->all());
    }

    #[Test]
    public function publications_filter_by_type_and_year(): void
    {
        Publication::factory()->type(PublicationType::Book)->year(1999)->create(['title' => 'The Book']);
        Publication::factory()->type(PublicationType::Report)->year(2004)->create(['title' => 'The Report']);

        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->assertSee('data-filter="type"', false)
            ->assertSee('data-filter="year"', false)
            ->set('filters.type', 'book')
            ->assertSee('The Book')->assertDontSee('The Report')
            ->set('filters.type', '')
            ->set('filters.year', '2004')
            ->assertDontSee('The Book')->assertSee('The Report');
    }

    #[Test]
    public function members_cannot_use_the_component(): void
    {
        Livewire::actingAs(User::factory()->member()->create())->test(AwardIndex::class)->assertForbidden();
    }

    #[Test]
    public function the_empty_state_invites_the_first_record(): void
    {
        Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertSee('No awards yet. Create the first one.')
            ->assertSee(route('admin.content.awards.create'), false);
    }
}
