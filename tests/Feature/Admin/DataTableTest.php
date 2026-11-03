<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\ContentStatus;
use App\Constants\DataTable;
use App\Constants\ListToolbar;
use App\Constants\Locales;
use App\Livewire\Admin\Awards\Index as AwardIndex;
use App\Livewire\Admin\Publications\Index as PublicationIndex;
use App\Livewire\Admin\TeachingMaterials\Index as TeachingIndex;
use App\Livewire\Admin\Tributes\Moderate;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Models\Award;
use App\Models\ListPreference;
use App\Models\Publication;
use App\Models\TeachingMaterial;
use App\Models\Tribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The shared admin data table (x-admin.data-table + WithDataTable) on the
 * screens that use it: header sorting, the Columns menu remembered per user,
 * the page size, Reorder mode, rows and actions that follow permissions,
 * shift-click ranges, "Select all n matching" and pagination that keeps filters.
 */
class DataTableTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->editor()->create();
    }

    #[Test]
    public function the_table_has_a_primary_cell_status_chip_and_end_aligned_dates(): void
    {
        TeachingMaterial::factory()->published()->create(['title' => 'Soil lab sheet', 'course' => 'AGR 205 Principles of Agronomy']);

        $html = Livewire::actingAs($this->editor)->test(TeachingIndex::class)->html();

        $this->assertMatchesRegularExpression('/data-cell-type="primary".*data-row-link>Soil lab sheet<\/a>.*class="data-table__supporting"[^>]*>AGR 205 Principles of Agronomy · /s', $html);
        $this->assertStringContainsString('class="status-chip status-chip--cypress" data-status="cypress"', $html);
        $this->assertMatchesRegularExpression('/<td class="data-table__cell is-end[^"]*" data-cell="material_date" data-cell-type="date"/', $html);
        $this->assertMatchesRegularExpression('/<time datetime="[^"]+" title="[^"]+">[^<]+ ago<\/time>/', $html);
        // Course and type sit in the supporting line, not columns, unless the user shows them.
        $this->assertStringNotContainsString('data-column="course"', $html);
        $this->assertStringNotContainsString('data-move="up"', $html);
    }

    #[Test]
    public function headers_sort_with_aria_sort_and_dates_start_newest_first(): void
    {
        Award::factory()->create(['name' => 'Bronze cup', 'year' => 1990]);
        Award::factory()->create(['name' => 'Gold medal', 'year' => 2010]);

        $test = Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertSee('data-sort="year"', false)
            ->call('sortBy', 'year')
            ->assertSet('sort', 'year')
            ->assertSet('direction', ListToolbar::SORT_DESC);
        $this->assertMatchesRegularExpression('/data-column="year"\s+aria-sort="descending"\s*>(?:(?!<\/th>).)*data-sort="year"/s', $test->html());
        $this->assertMatchesRegularExpression('/Gold medal.*Bronze cup/s', $test->html());

        $test->call('sortBy', 'year')->assertSet('direction', ListToolbar::SORT_ASC);
        $this->assertMatchesRegularExpression('/Bronze cup.*Gold medal/s', $test->html());

        // Not a sortable column: nothing changes.
        $test->call('sortBy', 'status')->assertSet('sort', 'year');
    }

    #[Test]
    public function column_choices_are_remembered_per_user_and_screen(): void
    {
        Publication::factory()->create(['authors' => 'Khidir, M. O.']);

        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->assertSee('data-column-toggle="authors"', false)
            ->assertDontSee('data-column="authors"', false)
            ->call('setColumnVisible', 'authors', true)
            ->assertSee('data-column="authors"', false)
            ->call('setColumnVisible', 'updated_at', false)
            ->assertDontSee('data-column="updated_at"', false)
            // The primary column cannot be hidden.
            ->call('setColumnVisible', 'title', false)
            ->assertSee('data-column="title"', false);

        $preference = ListPreference::query()->where('user_id', $this->editor->id)->where('screen', AdminNavigation::PUBLICATIONS)->sole();
        $this->assertSame(['authors' => true, 'updated_at' => false], $preference->columns);

        // A new visit keeps them; another user sees the defaults.
        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->assertSee('data-column="authors"', false)
            ->assertDontSee('data-column="updated_at"', false)
            ->call('resetColumns')
            ->assertDontSee('data-column="authors"', false)
            ->assertSee('data-column="updated_at"', false);

        Livewire::actingAs(User::factory()->editor()->create())->test(PublicationIndex::class)
            ->assertDontSee('data-column="authors"', false);
    }

    #[Test]
    public function the_page_size_is_offered_validated_and_remembered(): void
    {
        Award::factory()->count(25)->create();

        Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertSee('data-per-page', false)
            ->assertSee('1–20 of 25')
            ->call('setPerPage', '50')
            ->assertSet('perPage', 50)
            ->assertSee('1–25 of 25')
            ->call('setPerPage', '7')
            ->assertSet('perPage', DataTable::DEFAULT_PAGE_SIZE)
            ->call('setPerPage', '100');

        $this->assertSame(100, ListPreference::query()->where('user_id', $this->editor->id)->value('per_page'));

        Livewire::actingAs($this->editor)->test(AwardIndex::class)->assertSet('perPage', 100);
        Livewire::withQueryParams([DataTable::PARAM_PER_PAGE => '50'])->actingAs($this->editor)->test(AwardIndex::class)->assertSet('perPage', 50);
    }

    #[Test]
    public function pagination_keeps_the_filters_and_page_size(): void
    {
        Award::factory()->published()->count(23)->create();
        Award::factory()->draft()->count(3)->create();

        Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->set('filters.status', ContentStatus::Published->value)
            ->assertSee('1–20 of 23')
            ->assertSee('data-page-number="2"', false)
            ->call('gotoPage', 2)
            ->assertSee('21–23 of 23')
            ->assertSet('filters', ['status' => ContentStatus::Published->value])
            // A new filter or page size goes back to page 1.
            ->call('setPerPage', 50)
            ->assertSee('1–23 of 23');
    }

    #[Test]
    public function reorder_mode_replaces_the_checkboxes_and_saves_the_order(): void
    {
        $a = Award::factory()->sortOrder(1)->create(['name' => 'A']);
        $b = Award::factory()->sortOrder(2)->create(['name' => 'B']);
        $c = Award::factory()->sortOrder(3)->create(['name' => 'C']);

        $test = Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertSee('data-select-row', false)
            ->assertSee('data-reorder-toggle', false)
            ->call('startReordering')
            ->assertSet('reordering', true)
            ->assertDontSee('data-select-row', false)
            ->assertSee('data-reorder-bar', false)
            ->assertSee('data-move="down"', false)
            ->call('cancelReordering')
            ->assertSet('reordering', false)
            ->call('startReordering')
            ->call('saveOrder', [$c->id, $a->id, $b->id])
            ->assertSet('reordering', false)
            ->assertSee('Order saved.');

        $this->assertSame(['C', 'A', 'B'], Award::query()->ordered()->pluck('name')->all());

        // Not while searching or sorted: the toggle is gone and the server refuses.
        $test->set('search', 'A')->assertDontSee('data-reorder-toggle', false)->call('startReordering')->assertSet('reordering', false);
        $test->set('search', '')->call('sortBy', 'name')->call('saveOrder', [$a->id, $b->id, $c->id]);
        $this->assertSame(['C', 'A', 'B'], Award::query()->ordered()->pluck('name')->all());
    }

    #[Test]
    public function row_links_and_actions_follow_the_permissions(): void
    {
        $publication = Publication::factory()->published()->create(['title' => 'Water use']);

        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->assertSee('data-href="'.route('admin.content.publications.edit', ['publication' => $publication->id]).'"', false)
            ->assertSee('data-row-link', false)
            ->assertSee('data-edit="data-edit"', false)
            ->assertSee('data-duplicate="data-duplicate"', false)
            ->assertSee('data-delete="data-delete"', false)
            ->assertSee('data-status-toggle', false);

        // Someone who may see the list but not change these records gets no link, no actions and no switch.
        Gate::before(fn (User $user, string $ability): ?bool => in_array($ability, ['update', 'delete', 'publish', 'create', 'reorder'], true) ? false : null);

        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->assertSee('Water use')
            ->assertDontSee('data-row-link', false)
            ->assertDontSee('data-href=', false)
            ->assertDontSee('data-edit="data-edit"', false)
            ->assertDontSee('data-delete="data-delete"', false)
            ->assertDontSee('data-status-toggle', false)
            ->assertSee('data-view-on-site', false)
            ->call('trash', $publication->id)
            ->assertForbidden();
    }

    #[Test]
    public function the_signed_in_admin_has_no_actions_on_their_own_row(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Root Admin']);
        $other = User::factory()->editor()->create(['name' => 'Other Editor']);

        $html = Livewire::actingAs($admin)->test(UsersIndex::class)->html();

        $this->assertMatchesRegularExpression('/data-user="'.$admin->id.'"/', $html);
        $this->assertDoesNotMatchRegularExpression('/data-user="'.$admin->id.'"(?:(?!<\/tr>).)*data-row-actions/s', $html);
        $this->assertMatchesRegularExpression('/data-user="'.$other->id.'"(?:(?!<\/tr>).)*data-row-actions/s', $html);
        $this->assertStringContainsString('wire:click="editRole('.$other->id.')"', $html);
        $this->assertStringNotContainsString('wire:click="editRole('.$admin->id.')"', $html);
    }

    #[Test]
    public function the_status_switch_publishes_with_undo(): void
    {
        $award = Award::factory()->draft()->create(['name' => 'Gold medal']);

        Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->assertSee('role="switch" class="data-table__switch" aria-checked="false"', false)
            ->call('togglePublished', $award->id)
            ->assertDispatched('admin-toast', fn (string $event, array $params): bool => ($params['undo']['method'] ?? null) === 'togglePublished' && $params['undo']['args'] === [$award->id]);

        $this->assertSame(ContentStatus::Published, $award->fresh()->status);

        Livewire::actingAs($this->editor)->test(AwardIndex::class)->call('togglePublished', $award->id);
        $this->assertSame(ContentStatus::Draft, $award->fresh()->status);
    }

    #[Test]
    public function duplicate_makes_a_draft_copy_and_opens_it(): void
    {
        $award = Award::factory()->published()->create(['name' => 'Gold medal']);

        Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->call('duplicate', $award->id)
            ->assertRedirect();

        $copy = Award::query()->whereKeyNot($award->id)->sole();
        $this->assertSame('Gold medal (copy)', $copy->getTranslation('name', Locales::EN));
        $this->assertSame(ContentStatus::Draft, $copy->status);
    }

    #[Test]
    public function shift_click_ticks_a_range_and_all_matching_rows_can_be_selected(): void
    {
        $awards = Award::factory()->count(25)->sequence(fn ($sequence) => ['sort_order' => $sequence->index + 1])->create();
        $ids = $awards->pluck('id')->all();
        $page = array_slice($ids, 0, 20);

        $test = Livewire::actingAs($this->editor)->test(AwardIndex::class)
            ->call('selectRows', [$ids[2]], true)
            ->call('selectRange', $ids[2], $ids[6], true, $page)
            ->assertSet('selected', array_slice($ids, 2, 5))
            ->call('selectRange', $ids[6], $ids[4], false, $page)
            ->assertSet('selected', array_slice($ids, 2, 2))
            ->call('clearSelection')
            ->call('selectPage', $page)
            ->assertSee('data-select-all-matching', false)
            ->assertSee('Select all 25 matching')
            ->call('selectAllMatching');

        $this->assertEqualsCanonicalizing($ids, $test->get('selected'));
        $test->assertSee('data-all-selected-notice', false)->assertSee('data-bulk-bar', false);
    }

    #[Test]
    public function tribute_rows_open_their_words_and_keep_the_moderation_actions(): void
    {
        $tribute = Tribute::factory()->pending()->create(['name' => 'Amna', 'message' => 'He taught us well.', 'location' => 'Wad Medani']);

        $html = Livewire::actingAs($this->editor)->test(Moderate::class)->html();

        $this->assertMatchesRegularExpression('/data-tribute="'.$tribute->id.'".*Amna.*data-table__supporting[^>]*>[^<]+ · Wad Medani/s', $html);
        $this->assertStringContainsString('data-disclose', $html);
        $this->assertMatchesRegularExpression('/data-row-detail="'.$tribute->id.'".*He taught us well\./s', $html);
        $this->assertStringContainsString('wire:click="approve('.$tribute->id.')" data-approve="data-approve"', $html);
        $this->assertStringContainsString('wire:click="decline('.$tribute->id.')" data-reject="data-reject"', $html);
    }

    #[Test]
    public function the_table_reads_in_arabic(): void
    {
        Award::factory()->create(['name' => 'Gold medal']);
        $arabic = User::factory()->editor()->arabic()->create();

        $this->actingAs($arabic)->get(route('admin.content.awards'))
            ->assertOk()
            ->assertSee(__('data-table.columns', [], Locales::AR))
            ->assertSee(__('data-table.per_page', [], Locales::AR))
            ->assertSee(__('Drafts', [], Locales::AR))
            ->assertSee('1–1 من 1')
            ->assertDontSee('>Showing', false);
    }
}
