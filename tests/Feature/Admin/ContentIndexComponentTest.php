<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminUx;
use App\Constants\AuditAction;
use App\Constants\ContentStatus;
use App\Constants\ContentTypes;
use App\Constants\PublicationType;
use App\Livewire\Admin\Awards\Index as AwardIndex;
use App\Livewire\Admin\Publications\Index as PublicationIndex;
use App\Models\AuditLog;
use App\Models\Award;
use App\Models\Publication;
use App\Models\User;
use App\Repositories\Contracts\ContentRepositoryInterface;
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
            ->assertSee('data-move="up"', false)
            ->assertSee('data-move="down"', false);
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
    public function moving_to_the_trash_needs_no_dialog_offers_undo_and_can_be_restored(): void
    {
        $award = Award::factory()->published()->create(['name' => 'Gold medal']);

        $component = Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertDontSee('data-confirm-dialog', false)
            ->call('trash', $award->getKey())
            ->assertDontSee('data-confirm-dialog', false)
            ->assertDispatched(AdminUx::EVENT_TOAST, fn (string $name, array $params): bool => $params['message'] === 'Award moved to the trash.'
                && $params['undo']['method'] === 'restoreMany'
                && $params['undo']['args'] === [[$award->getKey()]]
                && $params['seconds'] === AdminUx::UNDO_SECONDS)
            ->assertDontSee('Gold medal')
            ->assertSee('Trash<span class="ml-2 text-xs text-ink-muted">1</span>', false);

        $this->assertNotNull($award->fresh()->deleted_at);

        // The toast's Undo calls restoreMany with the id.
        $component->call('restoreMany', [$award->getKey()])
            ->assertSee('Award restored.')
            ->assertSee('Gold medal');
        $this->assertNull($award->fresh()->deleted_at);

        $component->call('trash', $award->getKey())
            ->call('showTrash')
            ->assertSet('view', 'trash')
            ->assertSee('Gold medal')
            ->assertSee('data-restore', false)
            ->call('restoreMany', [$award->getKey()])
            ->assertSee('The trash is empty.')
            ->call('showActive')
            ->assertSee('Gold medal');

        $this->assertNull($award->fresh()->deleted_at);
        $this->assertSame(
            [AuditAction::CONTENT_DELETED, AuditAction::CONTENT_RESTORED, AuditAction::CONTENT_DELETED, AuditAction::CONTENT_RESTORED],
            AuditLog::query()->orderBy('id')->pluck('action')->all(),
        );
    }

    #[Test]
    public function ticked_rows_are_published_unpublished_or_trashed_together_from_the_bulk_bar(): void
    {
        $a = Award::factory()->draft()->create(['name' => 'Alpha prize']);
        $b = Award::factory()->draft()->create(['name' => 'Beta prize']);
        $c = Award::factory()->published()->create(['name' => 'Gamma prize']);

        $component = Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertDontSee('data-bulk-bar', false)
            ->set('selected', [$a->getKey(), $b->getKey()])
            ->assertSee('data-bulk-bar', false)
            ->assertSee('2</span> selected', false)
            ->call('bulkPublish')
            ->assertSet('selected', [])
            ->assertSee('2 awards published.');

        $this->assertTrue($a->fresh()->isPublished());
        $this->assertTrue($b->fresh()->isPublished());

        $component->call('selectPage', [$a->getKey(), $c->getKey()])
            ->assertSet('selected', [$a->getKey(), $c->getKey()])
            ->call('bulkUnpublish')
            ->assertSee('2 moved back to draft.');

        $this->assertTrue($a->fresh()->isDraft());
        $this->assertTrue($c->fresh()->isDraft());

        $component->set('selected', [$a->getKey(), $b->getKey(), $c->getKey()])
            ->call('bulkTrash')
            ->assertDispatched(AdminUx::EVENT_TOAST, fn (string $name, array $params): bool => $params['undo']['method'] === 'restoreMany' && count($params['undo']['args'][0]) === 3)
            ->assertSee('No awards yet');

        $this->assertSame(3, Award::onlyTrashed()->count());

        $component->call('restoreMany', [$a->getKey(), $b->getKey(), $c->getKey()])->assertSee('3 restored.');
        $this->assertSame(0, Award::onlyTrashed()->count());
    }

    #[Test]
    public function filter_chips_set_and_clear_filters_in_the_address(): void
    {
        Award::factory()->published()->create(['name' => 'Gold medal', 'organisation' => 'Royal Society', 'description' => null]);
        Award::factory()->draft()->create(['name' => '[Placeholder] Silver medal']);

        Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertSee('aria-pressed="true"', false)
            ->call('setFilter', 'status', ContentStatus::Draft->value)
            ->assertSet('filters', ['status' => 'draft'])
            ->assertDontSee('Gold medal')->assertSee('Silver medal')
            ->call('setFilter', 'status')
            ->assertSet('filters', [])
            ->call('setFilter', ContentRepositoryInterface::FILTER_PLACEHOLDER, '1')
            ->assertDontSee('Gold medal')->assertSee('Silver medal')
            ->call('setFilter', 'password', 'x')
            ->assertSet('filters', [ContentRepositoryInterface::FILTER_PLACEHOLDER => '1'])
            ->set('search', 'nothing like this')
            ->assertSee('Nothing matches')
            ->assertSee('Clear search and filters')
            ->call('clearFilters')
            ->assertSet('filters', [])
            ->assertSet('search', '')
            ->assertSee('Gold medal');
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
            ->assertSee('No awards yet')
            ->assertSee(ContentTypes::emptyText(ContentTypes::AWARD))
            ->assertSee('Add the first award')
            ->assertSee(route('admin.content.awards.create'), false);
    }
}
