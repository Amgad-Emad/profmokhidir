<?php

namespace Tests\Feature\Admin;

use App\Constants\AdminNavigation;
use App\Constants\ContentStatus;
use App\Constants\ListDensity;
use App\Constants\ListToolbar;
use App\Constants\PublicationType;
use App\Livewire\Admin\Community\Applications;
use App\Livewire\Admin\Publications\Index as PublicationIndex;
use App\Livewire\Admin\Tributes\Moderate;
use App\Models\ListPreference;
use App\Models\Publication;
use App\Models\Tribute;
use App\Models\User;
use App\Services\ListToolbarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * How the list toolbar behaves, through the publications and tributes screens:
 * counts, chips and clearing, the bulk bar in the toolbar's place, the filters
 * sheet, sorting, density, the trash view and the remembered view.
 */
class ListToolbarTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->editor = User::factory()->editor()->create();

        Publication::factory()->published()->create(['title' => 'Soil survey', 'type' => PublicationType::Book, 'year' => 1990]);
        Publication::factory()->published()->featured()->create(['title' => 'Wheat yields', 'type' => PublicationType::JournalArticle, 'year' => 2001]);
        Publication::factory()->draft()->create(['title' => '[Placeholder] Soil chemistry', 'type' => PublicationType::Report, 'year' => 1990]);
    }

    #[Test]
    public function views_and_options_show_their_counts_and_filters_narrow_them(): void
    {
        $test = Livewire::actingAs($this->editor)->test(PublicationIndex::class);
        $toolbar = $test->instance()->listToolbar(3);
        $counts = array_column($toolbar['views'], 'count', 'key');

        $this->assertSame(['all' => 3, 'drafts' => 1, 'published' => 2, 'needs-arabic' => 3, 'placeholder' => 3, 'featured' => 1, 'trash' => 0], $counts);
        $this->assertSame(['Status', 'Type', 'Year', 'Featured', 'Language', 'Sample text'], array_column($toolbar['filters'], 'label'));
        $this->assertFalse($toolbar['filters'][5]['toolbar']);

        $test->call('toggleFilter', 'type', 'book')
            ->call('toggleFilter', 'type', 'report')
            ->assertSet('filters', ['type' => 'book,report'])
            ->assertSee('Soil survey')->assertSee('Soil chemistry')->assertDontSee('Wheat yields')
            ->assertSee('Showing 2 of 3')
            ->assertSee('Type: Book +1')
            ->assertSee('data-filter-chip="type"', false);

        $status = collect($test->instance()->listToolbar(2)['filters'])->firstWhere('key', 'status');
        $this->assertSame([1, 1], array_column($status['options'], 'count'));

        // The view the filters match is shown as selected; a custom mix selects none.
        $test->call('toggleFilter', 'status', 'draft');
        $this->assertSame('', $test->instance()->activeView());
        $test->call('removeFilter', 'type', 'book')->call('removeFilter', 'type', 'report');
        $this->assertSame('drafts', $test->instance()->activeView());
        $test->assertSet('tab', 'drafts');
    }

    #[Test]
    public function clearing_brings_everything_back_and_an_empty_result_is_not_an_empty_list(): void
    {
        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->assertDontSee('data-list-no-match', false)
            ->set('search', 'nothing at all like this')
            ->assertSee('No items match these filters')
            ->assertSee('data-list-no-match', false)
            ->assertSee('Clear filters')
            ->assertSee('Showing 0 of 3')
            ->call('clearSearch')
            ->assertSet('search', '')
            ->call('setFilter', 'status', ContentStatus::Draft->value)
            ->call('setFilter', 'year', '1990')
            ->assertSee('data-clear-filters', false)
            ->call('clearFilters')
            ->assertSet('filters', [])
            ->assertSet('tab', '')
            ->assertDontSee('data-filter-chip', false)
            ->assertSee('Showing 3 of 3');

        Publication::query()->delete();
        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->assertSee('No publications yet')
            ->assertDontSee('No items match these filters');
    }

    #[Test]
    public function every_change_goes_back_to_the_first_page_and_tells_the_browser(): void
    {
        Publication::factory()->count(25)->published()->create(['type' => PublicationType::Book]);

        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->call('toggleFilter', 'type', 'book')
            ->assertSet('paginators.page', 1)
            ->assertDispatched(ListToolbar::EVENT_FILTERED)
            ->call('gotoPage', 2)
            ->set('search', 'a')
            ->assertSet('paginators.page', 1);
    }

    #[Test]
    public function ticked_rows_swap_the_toolbar_for_the_bulk_bar_in_the_same_place(): void
    {
        $ids = Publication::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $test = Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->assertSee('data-list-search', false)
            ->assertDontSee('data-bulk-bar', false)
            ->set('selected', [$ids[0], $ids[1]]);

        // The bar sits in the toolbar's own line, between the views and the result count.
        $this->assertMatchesRegularExpression('/data-list-views.*data-toolbar-row.*data-bulk-bar.*2 selected.*data-bulk="publish".*data-bulk="unpublish".*data-bulk="trash".*data-bulk="clear".*data-result-count/s', $test->html());

        $test->assertDontSee('data-list-search', false)
            ->call('clearSelection')
            ->assertSee('data-list-search', false)
            ->assertDontSee('data-bulk-bar', false)
            // A filter change clears the selection, so the bar never acts on hidden rows.
            ->set('selected', [$ids[0]])
            ->call('toggleFilter', 'type', 'book')
            ->assertSet('selected', []);

        $tribute = Tribute::factory()->pending()->create();
        Livewire::actingAs($this->editor)->test(Moderate::class)
            ->set('selected', [$tribute->getKey()])
            ->assertSeeInOrder(['data-bulk-bar', 'data-bulk-approve', 'data-bulk-reject'], false);
    }

    #[Test]
    public function the_filters_sheet_applies_filters_sort_and_density_in_one_go(): void
    {
        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->assertSee('data-filters-sheet', false)
            ->call('applyFilters', ['status' => [], 'type' => ['report', 'book', 'poem'], 'year' => ['1990'], 'bogus' => ['x']], 'title:asc', ListDensity::Compact->value)
            ->assertSet('filters', ['type' => 'book,report', 'year' => '1990'])
            ->assertSet('sort', 'title')
            ->assertSet('direction', 'asc')
            ->assertSet('density', 'compact')
            ->assertSee('data-density="compact"', false)
            ->call('applyFilters', [], ':asc')
            ->assertSet('filters', [])
            ->assertSet('sort', '');
    }

    #[Test]
    public function sorting_from_the_menu_accepts_only_the_screens_columns(): void
    {
        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->call('applySort', 'year', 'desc')
            ->assertSet('sort', 'year')
            ->assertSet('direction', 'desc')
            ->assertSeeInOrder(['Wheat yields', 'Soil'])
            ->call('applySort', 'password', 'asc')
            ->assertSet('sort', 'year')
            ->call('applySort', 'title', 'sideways')
            ->assertSet('direction', 'asc')
            ->call('applySort', '')
            ->assertSet('sort', '');

        Livewire::actingAs($this->editor)->withQueryParams(['sort' => 'password', 'direction' => 'up'])->test(PublicationIndex::class)
            ->assertSet('sort', '')
            ->assertSet('direction', 'asc');
    }

    #[Test]
    public function the_density_is_remembered_for_the_user_and_screen(): void
    {
        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->assertSee('data-density="comfortable"', false)
            ->call('toggleDensity')
            ->assertSee('data-density="compact"', false)
            ->assertSee('aria-pressed="true"', false);

        Livewire::actingAs($this->editor)->test(PublicationIndex::class)->assertSet('density', 'compact');
        Livewire::actingAs(User::factory()->editor()->create())->test(PublicationIndex::class)->assertSet('density', 'comfortable');
    }

    #[Test]
    public function the_last_used_view_comes_back_when_the_screen_is_opened_without_a_query(): void
    {
        $route = route(AdminNavigation::routeFor(AdminNavigation::PUBLICATIONS));

        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->call('applyView', 'drafts')
            ->set('search', 'soil');

        $this->assertSame(['q' => 'soil', 'filters' => ['status' => 'draft'], 'tab' => 'drafts'], app(ListToolbarService::class)->recall($this->editor, AdminNavigation::PUBLICATIONS));

        $this->actingAs($this->editor)->get($route)->assertRedirect($route.'?'.http_build_query(['q' => 'soil', 'filters' => ['status' => 'draft'], 'tab' => 'drafts']));
        $this->actingAs($this->editor)->get($route.'?q=wheat')->assertOk()->assertSee('Wheat yields')->assertDontSee('Soil survey');
        $this->actingAs($this->editor)->get($route.'?'.http_build_query(['q' => 'soil', 'filters' => ['status' => 'draft'], 'tab' => 'drafts']))
            ->assertOk()->assertSee('Soil chemistry')->assertDontSee('Soil survey');
        $this->assertSame(1, ListPreference::query()->where('user_id', $this->editor->getKey())->count());
    }

    #[Test]
    public function the_trash_and_a_cleared_list_are_not_remembered(): void
    {
        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->call('applyView', 'drafts')
            ->call('showTrash');

        $this->assertNull(app(ListToolbarService::class)->recall($this->editor, AdminNavigation::PUBLICATIONS));
        $this->actingAs($this->editor)->get(route(AdminNavigation::routeFor(AdminNavigation::PUBLICATIONS)))->assertOk()->assertSee('Soil survey');
    }

    #[Test]
    public function the_trash_view_searches_but_hides_the_filters(): void
    {
        Publication::query()->where('year', 2001)->first()->delete();

        Livewire::actingAs($this->editor)->test(PublicationIndex::class)
            ->call('applyView', ListToolbar::VIEW_TRASH)
            ->assertSet('tab', ListToolbar::VIEW_TRASH)
            ->assertSee('Wheat yields')
            ->assertDontSee('data-filter-buttons', false)
            ->assertDontSee('data-filters-sheet', false)
            ->set('search', 'nothing')
            ->assertSee('No items match these filters')
            ->call('clearFilters')
            ->assertSet('tab', ListToolbar::VIEW_TRASH)
            ->assertSee('Wheat yields')
            ->call('applyView', 'all')
            ->assertSet('tab', '')
            ->assertDontSee('Wheat yields');
    }

    #[Test]
    public function screens_open_on_their_own_default_view_and_bookmarked_tabs_set_their_filters(): void
    {
        Tribute::factory()->pending()->create(['name' => 'Pending Person']);
        Tribute::factory()->approved()->create(['name' => 'Approved Person']);
        $admin = User::factory()->admin()->create();
        // Before any withQueryParams(), which sticks to later tests in the same method.
        Livewire::actingAs($admin)->test(Applications::class)->assertSet('filters', ['community' => 'pending']);

        Livewire::actingAs($this->editor)->test(Moderate::class)
            ->assertSet('filters', ['status' => 'pending'])
            ->assertSee('Pending Person')->assertDontSee('Approved Person')
            ->call('applyView', ListToolbar::VIEW_ALL)
            ->assertSet('tab', ListToolbar::VIEW_ALL)
            ->assertSet('filters', [])
            ->assertSee('Approved Person');

        Livewire::actingAs($this->editor)->withQueryParams(['tab' => 'approved'])->test(Moderate::class)
            ->assertSet('filters', ['status' => 'approved'])
            ->assertSee('Approved Person')->assertDontSee('Pending Person');

        $this->actingAs($admin)->get(route(AdminNavigation::routeFor(AdminNavigation::MEMBERS)))->assertOk()
            ->assertSeeInOrder(['aria-selected="true"', 'data-view="approved"'], false);
    }
}
