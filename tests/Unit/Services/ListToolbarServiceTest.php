<?php

namespace Tests\Unit\Services;

use App\Constants\AdminNavigation;
use App\Constants\ListDensity;
use App\Constants\ListToolbar;
use App\Models\ListPreference;
use App\Models\User;
use App\Services\ListToolbarService;
use App\Support\ListToolbar\ListFilter;
use App\Support\ListToolbar\ListSort;
use App\Support\ListToolbar\ListView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The logic behind the admin list toolbar, with a made-up screen of ten
 * records (status draft or published, a type, and a needs-Arabic flag).
 */
class ListToolbarServiceTest extends TestCase
{
    use RefreshDatabase;

    private ListToolbarService $toolbar;

    /** @var list<array{status: string, type: string, arabic: bool, title: string}> */
    private array $records = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->toolbar = $this->app->make(ListToolbarService::class);

        foreach (range(1, 10) as $i) {
            $this->records[] = ['status' => $i <= 3 ? 'draft' : 'published', 'type' => $i % 2 === 0 ? 'book' : 'article', 'arabic' => $i > 7, 'title' => 'Record '.$i];
        }
    }

    /**
     * @return list<ListFilter>
     */
    private function filters(): array
    {
        return [
            ListFilter::single('status', 'Status', ['draft' => 'Draft', 'published' => 'Published']),
            ListFilter::multi('type', 'Type', ['book' => 'Book', 'article' => 'Article', 'report' => 'Report']),
            ListFilter::flag('needs_arabic', 'Language', 'Needs Arabic translation', 'Needs Arabic'),
        ];
    }

    /**
     * @return list<ListView>
     */
    private function views(): array
    {
        return [
            new ListView(ListToolbar::VIEW_ALL, 'All'),
            new ListView('drafts', 'Drafts', ['status' => 'draft']),
            new ListView('needs-arabic', 'Needs Arabic', ['needs_arabic' => '1']),
            new ListView('unfinished', 'Unfinished', ['status' => 'draft']),
            new ListView(ListToolbar::VIEW_TRASH, 'Trash', trash: true),
        ];
    }

    private function countRecords(array $criteria, string $search): int
    {
        return count(array_filter($this->records, fn (array $record): bool => (! isset($criteria['status']) || $record['status'] === $criteria['status'])
            && (! isset($criteria['type']) || in_array($record['type'], (array) $criteria['type'], true))
            && (! isset($criteria['needs_arabic']) || $record['arabic'])
            && ($search === '' || str_contains($record['title'], $search))));
    }

    #[Test]
    public function state_from_the_address_keeps_only_declared_filters_and_known_values(): void
    {
        $clean = $this->toolbar->normalise([
            'status' => 'draft',
            'type' => 'report,book,unknown',
            'needs_arabic' => 'yes',
            'password' => 'x',
            'nested' => ['a' => 'b'],
        ], $this->filters());

        $this->assertSame(['status' => 'draft', 'type' => 'book,report'], $clean);
        $this->assertSame(['status' => 'draft', 'type' => ['book', 'report']], $this->toolbar->criteria($clean, $this->filters()));
        $this->assertSame([], $this->toolbar->normalise(['type' => ['book']], $this->filters()));
    }

    #[Test]
    public function the_matching_view_prefers_the_current_one_and_ignores_the_trash(): void
    {
        $this->assertSame(ListToolbar::VIEW_ALL, $this->toolbar->matchingView([], $this->views(), $this->filters()));
        $this->assertSame('drafts', $this->toolbar->matchingView(['status' => 'draft'], $this->views(), $this->filters()));
        $this->assertSame('unfinished', $this->toolbar->matchingView(['status' => 'draft'], $this->views(), $this->filters(), 'unfinished'));
        $this->assertSame('', $this->toolbar->matchingView(['status' => 'draft', 'type' => 'book'], $this->views(), $this->filters()));
        $this->assertTrue($this->toolbar->view($this->views(), ListToolbar::VIEW_TRASH)->trash);
        $this->assertNull($this->toolbar->view($this->views(), 'nope'));
    }

    #[Test]
    public function the_initial_filters_are_the_default_views_over_each_filters_default(): void
    {
        $filters = [...$this->filters(), ListFilter::single('access', 'Access', ['public' => 'Public', 'members' => 'Members'], 'public')];

        $this->assertSame(['status' => 'draft', 'access' => 'public'], $this->toolbar->initialFilters($this->toolbar->view($this->views(), 'drafts'), $filters));
        $this->assertSame(['access' => 'public'], $this->toolbar->initialFilters(null, $filters));
    }

    #[Test]
    public function toggling_an_option_follows_the_filters_type(): void
    {
        [$status, $type, $flag] = $this->filters();

        $this->assertSame(['status' => 'published'], $this->toolbar->toggle(['status' => 'draft'], $status, 'published'));
        $this->assertSame([], $this->toolbar->toggle(['status' => 'draft'], $status, null));
        $this->assertSame(['type' => 'book,article'], $this->toolbar->toggle(['type' => 'article'], $type, 'book'));
        $this->assertSame([], $this->toolbar->toggle(['type' => 'book'], $type, 'book'));
        $this->assertSame(['needs_arabic' => '1'], $this->toolbar->toggle([], $flag, '1'));
        $this->assertSame([], $this->toolbar->toggle(['needs_arabic' => '1'], $flag, '1'));

        $this->assertSame(['type' => 'article'], $this->toolbar->remove(['type' => 'book,article'], $type, 'book'));
        $this->assertSame(['status' => 'draft'], $this->toolbar->remove(['status' => 'draft', 'type' => 'book,article'], $type));
    }

    #[Test]
    public function buttons_and_chips_say_what_is_set(): void
    {
        [$status, $type, $flag] = $this->filters();

        $this->assertSame('Status', $this->toolbar->buttonLabel($status, []));
        $this->assertSame('Status: Draft', $this->toolbar->buttonLabel($status, ['draft']));
        $this->assertSame('Type: Book +1', $this->toolbar->buttonLabel($type, ['book', 'report']));
        $this->assertSame('Language: Needs Arabic', $this->toolbar->buttonLabel($flag, ['1']));

        $this->assertSame([
            ['key' => 'status', 'value' => 'draft', 'label' => 'Status: Draft'],
            ['key' => 'type', 'value' => 'book', 'label' => 'Type: Book'],
            ['key' => 'type', 'value' => 'report', 'label' => 'Type: Report'],
            ['key' => 'needs_arabic', 'value' => '1', 'label' => 'Needs Arabic'],
        ], $this->toolbar->chips(['needs_arabic' => '1', 'type' => 'report,book', 'status' => 'draft'], $this->filters()));
    }

    #[Test]
    public function option_counts_apply_the_other_filters_and_the_search(): void
    {
        [$status, $type] = $this->filters();
        $count = fn (array $criteria, string $search): int => $this->countRecords($criteria, $search);

        // Type counts among drafts: records 1-3 are drafts, 2 is a book.
        $this->assertSame(['book' => 1, 'article' => 2, 'report' => 0], $this->toolbar->facets($type, ['status' => 'draft', 'type' => 'book'], '', $this->filters(), $count));
        // Status counts ignore the status filter itself but keep the type.
        $this->assertSame(['draft' => 1, 'published' => 4], $this->toolbar->facets($status, ['status' => 'draft', 'type' => 'book'], '', $this->filters(), $count));
        $this->assertSame(['draft' => 0, 'published' => 1], $this->toolbar->facets($status, [], 'Record 10', $this->filters(), $count));

        $grouped = fn (string $key, array $criteria, string $search): array => ['book' => 7];
        $this->assertSame(['book' => 7, 'article' => 0, 'report' => 0], $this->toolbar->facets($type, [], '', $this->filters(), $count, $grouped));
    }

    #[Test]
    public function describe_gives_the_toolbar_everything_it_draws(): void
    {
        $count = fn (array $criteria, string $search): int => $this->countRecords($criteria, $search);
        $viewCount = fn (ListView $view): int => $view->trash ? 2 : $this->countRecords($this->toolbar->criteria($view->filters, $this->filters()), '');
        $sorts = [new ListSort('', 'asc', 'Display order'), new ListSort('title', 'asc', 'Title, A to Z')];

        $toolbar = $this->toolbar->describe($this->filters(), $this->views(), $sorts, [
            'search' => '', 'filters' => ['status' => 'draft'], 'sort' => 'title', 'direction' => 'asc',
            'tab' => 'drafts', 'density' => ListDensity::Compact, 'shown' => 3,
        ], $count, $viewCount);

        $this->assertSame([['all', 10, false], ['drafts', 3, true], ['needs-arabic', 3, false], ['unfinished', 3, false], ['trash', 2, false]], array_map(fn (array $view): array => [$view['key'], $view['count'], $view['active']], $toolbar['views']));
        $this->assertSame('Status: Draft', $toolbar['filters'][0]['button']);
        $this->assertSame([['draft', 3, true], ['published', 7, false]], array_map(fn (array $option): array => [$option['value'], $option['count'], $option['checked']], $toolbar['filters'][0]['options']));
        $this->assertSame('menuitemradio', $toolbar['filters'][0]['role']);
        $this->assertSame('menuitemcheckbox', $toolbar['filters'][1]['role']);
        $this->assertSame(1, $toolbar['activeFilters']);
        $this->assertSame('Title, A to Z', $toolbar['sortLabel']);
        $this->assertTrue($toolbar['sortActive']);
        $this->assertSame(['compact', 'comfortable'], [$toolbar['density'], $toolbar['nextDensity']]);
        $this->assertSame([3, 10, true, false], [$toolbar['shown'], $toolbar['total'], $toolbar['filtered'], $toolbar['trash']]);

        $trash = $this->toolbar->describe($this->filters(), $this->views(), $sorts, [
            'search' => '', 'filters' => [], 'sort' => '', 'direction' => 'asc', 'tab' => 'trash', 'density' => ListDensity::Comfortable, 'shown' => 2,
        ], $count, $viewCount);

        $this->assertTrue($trash['trash']);
        $this->assertSame([], $trash['chips']);
        $this->assertNull($trash['filters'][0]['options'][0]['count']);
        $this->assertSame('Display order', $trash['sortLabel']);
        $this->assertFalse($trash['sortActive']);
    }

    #[Test]
    public function the_last_view_and_density_are_remembered_per_user_and_screen(): void
    {
        $user = User::factory()->editor()->create();
        $other = User::factory()->editor()->create();

        $this->assertNull($this->toolbar->recall($user, AdminNavigation::PUBLICATIONS));
        $this->assertSame(ListDensity::Comfortable, $this->toolbar->density($user, AdminNavigation::PUBLICATIONS));

        $this->toolbar->remember($user, AdminNavigation::PUBLICATIONS, [
            'q' => 'soil', 'filters' => ['status' => 'draft', 'type' => '', 'bad' => ['x']], 'sort' => 'year', 'direction' => 'desc', 'tab' => '', 'page' => 4,
        ]);
        $this->toolbar->setDensity($user, AdminNavigation::PUBLICATIONS, ListDensity::Compact);

        $this->assertSame(['q' => 'soil', 'filters' => ['status' => 'draft'], 'sort' => 'year', 'direction' => 'desc'], $this->toolbar->recall($user, AdminNavigation::PUBLICATIONS));
        $this->assertSame(ListDensity::Compact, $this->toolbar->density($user, AdminNavigation::PUBLICATIONS));
        $this->assertNull($this->toolbar->recall($user, AdminNavigation::AWARDS));
        $this->assertNull($this->toolbar->recall($other, AdminNavigation::PUBLICATIONS));

        // Clearing everything forgets the view (an empty state restores nothing), the density stays.
        $this->toolbar->remember($user, AdminNavigation::PUBLICATIONS, ['q' => '', 'filters' => []]);
        $this->assertNull($this->toolbar->recall($user, AdminNavigation::PUBLICATIONS));
        $this->assertSame(ListDensity::Compact, $this->toolbar->density($user, AdminNavigation::PUBLICATIONS));
        $this->assertSame(1, ListPreference::query()->where('user_id', $user->getKey())->count());

        $this->assertSame(['q' => str_repeat('a', ListToolbar::MAX_SEARCH_LENGTH), 'tab' => 'drafts'], $this->toolbar->query(['q' => str_repeat('a', 500), 'tab' => 'drafts', 'direction' => 'desc']));
    }
}
