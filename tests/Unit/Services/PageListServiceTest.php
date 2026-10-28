<?php

namespace Tests\Unit\Services;

use App\Constants\ContentStatus;
use App\Constants\ListToolbar;
use App\Constants\Pages;
use App\Models\User;
use App\Services\PageListService;
use App\Services\PageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin › Pages as a list: searched, filtered and sorted in memory.
 */
class PageListServiceTest extends TestCase
{
    use RefreshDatabase;

    private PageService $pages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pages = $this->app->make(PageService::class);
        $this->pages->install();
    }

    private function list(): PageListService
    {
        // A new list each time: it reads the pages once per request.
        return $this->app->make(PageListService::class);
    }

    #[Test]
    public function every_page_is_listed_in_site_order_with_what_the_filters_need(): void
    {
        $rows = $this->list()->rows();

        $this->assertSame(Pages::all(), $rows->map(fn (array $row): string => $row['page']->key)->all());
        $this->assertSame(count(Pages::all()), $this->list()->count());
        $this->assertSame(__(Pages::get(Pages::HOME)['title']), $rows->first()['title']);
        $this->assertStringContainsString('/edit', $rows->first()['editUrl']);
    }

    #[Test]
    public function search_status_unpublished_changes_and_sorting_narrow_the_list(): void
    {
        $editor = User::factory()->editor()->create();
        $home = $this->pages->findByKey(Pages::HOME);
        $this->pages->saveDraft($home, ['hero_label' => 'Changed']);
        $this->pages->unpublish($this->pages->findByKey(Pages::ABOUT), $editor);

        $keys = fn (string $search = '', array $criteria = [], string $sort = '', string $direction = ListToolbar::SORT_ASC): array => $this->list()->rows($search, $criteria, $sort, $direction)->map(fn (array $row): string => $row['page']->key)->all();

        $this->assertSame([Pages::HOME], $keys('', [PageListService::FILTER_CHANGES => ListToolbar::FLAG_ON]));
        $this->assertSame([Pages::ABOUT], $keys('', [PageListService::FILTER_STATUS => ContentStatus::Draft->value]));
        $this->assertContains(Pages::HOME, $keys(mb_strtoupper(__(Pages::get(Pages::HOME)['title']))));
        $this->assertSame(1, $this->list()->count('', [PageListService::FILTER_CHANGES => ListToolbar::FLAG_ON]));

        $titles = $this->list()->rows('', [], PageListService::SORT_TITLE)->pluck('title')->all();
        $sorted = $titles;
        natcasesort($sorted);
        $this->assertSame(array_values($sorted), $titles);
        $this->assertSame(array_reverse($titles), $this->list()->rows('', [], PageListService::SORT_TITLE, ListToolbar::SORT_DESC)->pluck('title')->all());
        $this->assertSame(count(Pages::all()), $this->list()->rows('', [], PageListService::SORT_SAVED, ListToolbar::SORT_DESC)->count());
    }
}
