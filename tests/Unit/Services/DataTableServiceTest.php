<?php

namespace Tests\Unit\Services;

use App\Constants\AdminNavigation;
use App\Constants\DataTable;
use App\Constants\DataTableAlign;
use App\Constants\DataTableColumnType;
use App\Models\User;
use App\Services\DataTableService;
use App\Support\DataTable\Column;
use App\Support\DataTable\Row;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The logic behind the shared admin data table: visible columns and their
 * remembered choices, page sizes, shift-click ranges, the footer's range and
 * pages, dates, and the description the component draws.
 */
class DataTableServiceTest extends TestCase
{
    use RefreshDatabase;

    private DataTableService $tables;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tables = $this->app->make(DataTableService::class);
    }

    /**
     * @return list<Column>
     */
    private function columns(): array
    {
        return [
            Column::primary('title', 'Title', sortable: true),
            Column::text('course', 'Course', visible: false),
            Column::date('material_date', 'Date'),
            Column::status('status', 'Status'),
        ];
    }

    #[Test]
    public function types_align_text_to_the_start_and_figures_dates_and_status_to_the_end(): void
    {
        $this->assertSame(DataTableAlign::Start, Column::text('a', 'A')->align);
        $this->assertSame(DataTableAlign::End, Column::number('a', 'A')->align);
        $this->assertSame(DataTableAlign::End, Column::date('a', 'A')->align);
        $this->assertSame(DataTableAlign::End, Column::relative('a', 'A')->align);
        $this->assertSame(DataTableAlign::End, Column::status('a', 'A')->align);
        $this->assertTrue(DataTableColumnType::Relative->descendsFirst());
        $this->assertFalse(DataTableColumnType::Text->descendsFirst());
        $this->assertFalse(Column::primary('t', 'T')->hideable());
    }

    #[Test]
    public function visible_columns_follow_the_defaults_then_the_users_choices(): void
    {
        $keys = fn (array $columns): array => array_map(fn (Column $column): string => $column->key, $columns);

        $this->assertSame(['title', 'material_date', 'status'], $keys($this->tables->visibleColumns($this->columns(), [])));
        $this->assertSame(['title', 'course', 'status'], $keys($this->tables->visibleColumns($this->columns(), ['course' => true, 'material_date' => false, 'title' => false])));
    }

    #[Test]
    public function column_choices_are_stored_per_user_and_screen(): void
    {
        $user = User::factory()->editor()->create();

        $choices = $this->tables->setColumnVisible($user, AdminNavigation::TEACHING, $this->columns(), [], 'course', true);
        $choices = $this->tables->setColumnVisible($user, AdminNavigation::TEACHING, $this->columns(), $choices, 'title', false);
        $choices = $this->tables->setColumnVisible($user, AdminNavigation::TEACHING, $this->columns(), $choices + ['gone' => true], 'nope', false);

        $this->assertSame(['course' => true], $this->tables->columnChoices($user, AdminNavigation::TEACHING));
        $this->assertSame([], $this->tables->columnChoices($user, AdminNavigation::PUBLICATIONS));
        $this->assertSame([], $this->tables->columnChoices(null, AdminNavigation::TEACHING));

        $this->tables->resetColumns($user, AdminNavigation::TEACHING);
        $this->assertSame([], $this->tables->columnChoices($user, AdminNavigation::TEACHING));
    }

    #[Test]
    public function page_sizes_are_limited_to_the_offered_ones_and_remembered(): void
    {
        $user = User::factory()->editor()->create();

        $this->assertSame(50, $this->tables->normalisePerPage('50'));
        $this->assertSame(DataTable::DEFAULT_PAGE_SIZE, $this->tables->normalisePerPage(7));
        $this->assertSame(DataTable::DEFAULT_PAGE_SIZE, $this->tables->normalisePerPage(null));
        $this->assertSame(DataTable::DEFAULT_PAGE_SIZE, $this->tables->rememberedPerPage($user, AdminNavigation::AWARDS));

        $this->assertSame(100, $this->tables->setPerPage($user, AdminNavigation::AWARDS, 100));
        $this->assertSame(100, $this->tables->rememberedPerPage($user, AdminNavigation::AWARDS));
        $this->assertSame(DataTable::DEFAULT_PAGE_SIZE, $this->tables->rememberedPerPage($user, AdminNavigation::USERS));
    }

    #[Test]
    public function a_range_runs_in_page_order_either_way(): void
    {
        $order = [9, 4, 7, 2, 5];

        $this->assertSame([4, 7, 2], $this->tables->range($order, 4, 2));
        $this->assertSame([4, 7, 2], $this->tables->range($order, 2, 4));
        $this->assertSame([5], $this->tables->range($order, 99, 5));
        $this->assertSame([], $this->tables->range($order, 4, 99));
    }

    #[Test]
    public function the_footer_gives_the_range_and_a_short_run_of_pages(): void
    {
        $page = new LengthAwarePaginator(range(1, 20), 184, 20, 5);

        $this->assertSame(['from' => 81, 'to' => 100, 'total' => 184, 'label' => '81–100 of 184'], $this->tables->summary($page));
        $this->assertSame([1, null, 4, 5, 6, null, 10], $this->tables->pages($page));
        $this->assertSame([1, 2, 3], $this->tables->pages(new LengthAwarePaginator([], 50, 20, 1)));
        $this->assertSame(['from' => 0, 'to' => 0, 'total' => 0, 'label' => '0–0 of 0'], $this->tables->summary(new LengthAwarePaginator([], 0, 20, 1)));
    }

    #[Test]
    public function moments_are_relative_with_the_exact_time_and_content_dates_short(): void
    {
        Carbon::setTestNow('2026-10-28 12:00:00');

        $cell = $this->tables->relative(Carbon::parse('2026-10-28 11:30:00'));
        $this->assertSame('30 minutes ago', $cell->text);
        $this->assertSame('28 October 2026, 11:30', $cell->tooltip);
        $this->assertSame('2026-10-28T11:30:00+00:00', $cell->datetime);

        $date = $this->tables->date(Carbon::parse('2011-10-28'));
        $this->assertSame('28 Oct 2011', $date->text);
        $this->assertSame('2011-10-28', $date->datetime);
        $this->assertSame('1978', $this->tables->date(1978)->text);
        $this->assertTrue($this->tables->relative(null)->isEmpty());

        app()->setLocale('ar');
        Carbon::setLocale('ar');
        $this->assertSame('منذ 30 دقيقة', $this->tables->relative(Carbon::parse('2026-10-28 11:30:00'))->text);
        Carbon::setLocale('en');
    }

    #[Test]
    public function the_description_offers_select_all_only_once_the_page_is_ticked(): void
    {
        $rows = [new Row(1, 'One'), new Row(2, 'Two'), new Row(3, 'Three', selectable: false)];
        $page = new LengthAwarePaginator($rows, 24, 20, 1);

        $table = $this->tables->describe($this->columns(), [], $rows, $page, ['selectable' => true, 'selected' => [1], 'sortable' => ['title']]);
        $this->assertSame([1, 2], $table['pageIds']);
        $this->assertFalse($table['pageSelected']);
        $this->assertFalse($table['offerSelectAll']);
        $this->assertSame([true, false, false], array_map(fn (array $entry): bool => $entry['sortable'], $table['columns']));
        $this->assertSame(['course', 'material_date', 'status'], array_column($table['menuColumns'], 'key'));

        $table = $this->tables->describe($this->columns(), [], $rows, $page, ['selectable' => true, 'selected' => [1, 2]]);
        $this->assertTrue($table['offerSelectAll']);
        $this->assertSame(24, $table['matching']);

        $reordering = $this->tables->describe($this->columns(), [], $rows, $page, ['selectable' => true, 'reordering' => true]);
        $this->assertFalse($reordering['selectable']);
        $this->assertTrue($reordering['reordering']);
    }
}
