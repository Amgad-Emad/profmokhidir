<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ListToolbar;
use App\Constants\Pages;
use App\Models\Page;
use Illuminate\Support\Collection;

/**
 * Admin › Pages as a list: every page in site order with its status, whether
 * it has unpublished changes and whether Arabic is missing, then searched,
 * filtered and sorted in memory (there are only a couple of dozen pages, all
 * fixed by the design). Filter keys are the FILTER_* constants.
 */
class PageListService
{
    public const string FILTER_STATUS = 'status';

    public const string FILTER_CHANGES = 'changes';

    public const string FILTER_NEEDS_ARABIC = 'needs_arabic';

    /** Sort keys besides the site order (''). */
    public const string SORT_TITLE = 'title';

    public const string SORT_SAVED = 'updated_at';

    /** @var Collection<int, array{page: Page, definition: array<string, mixed>, title: string, summary: string, hasDraft: bool, needsArabic: bool, editUrl: string}>|null */
    private ?Collection $rows = null;

    public function __construct(private readonly PageService $pages) {}

    /**
     * The rows matching the search and criteria, sorted.
     *
     * @param  array<string, string|list<string>>  $criteria
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(string $search = '', array $criteria = [], string $sort = '', string $direction = ListToolbar::SORT_ASC): Collection
    {
        $rows = $this->filtered($search, $criteria);

        $sorted = match ($sort) {
            self::SORT_TITLE => $rows->sortBy(fn (array $row): string => mb_strtolower($row['title']), SORT_NATURAL, $direction === ListToolbar::SORT_DESC),
            self::SORT_SAVED => $rows->sortBy(fn (array $row): int => $row['page']->updated_at?->getTimestamp() ?? 0, SORT_NUMERIC, $direction === ListToolbar::SORT_DESC),
            default => $rows,
        };

        return $sorted->values();
    }

    /**
     * @param  array<string, string|list<string>>  $criteria
     */
    public function count(string $search = '', array $criteria = []): int
    {
        return $this->filtered($search, $criteria)->count();
    }

    /**
     * @param  array<string, string|list<string>>  $criteria
     * @return Collection<int, array<string, mixed>>
     */
    private function filtered(string $search, array $criteria): Collection
    {
        $term = mb_strtolower(trim($search));
        $status = $criteria[self::FILTER_STATUS] ?? null;

        return $this->all()->filter(fn (array $row): bool => ($term === '' || str_contains(mb_strtolower($row['title'].' '.$row['summary']), $term))
            && ($status === null || $row['page']->status->value === $status)
            && (! isset($criteria[self::FILTER_CHANGES]) || $row['hasDraft'])
            && (! isset($criteria[self::FILTER_NEEDS_ARABIC]) || $row['needsArabic']));
    }

    /**
     * Every page once per request, with what the list shows and filters on.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function all(): Collection
    {
        return $this->rows ??= collect($this->pages->all()->all())->map(function (Page $page): array {
            $definition = Pages::get($page->key);

            return [
                'page' => $page,
                'definition' => $definition,
                'title' => __($definition['title']),
                'summary' => __($definition['summary']),
                'hasDraft' => $this->pages->hasDraft($page),
                'needsArabic' => $this->pages->needsArabic($page),
                'editUrl' => route(Pages::EDIT_ROUTE, [Pages::ROUTE_PARAMETER => $page->key]),
            ];
        })->values();
    }
}
