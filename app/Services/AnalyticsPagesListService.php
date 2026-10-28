<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ListToolbar;

/**
 * The "Most engaging pages" list of /admin/analytics as an admin list: the
 * period's page rows (AnalyticsReportService::pageRows) searched by title or
 * path, filtered by section and sorted by engaged time, views, time per view or
 * scroll depth, in memory.
 */
class AnalyticsPagesListService
{
    public const string FILTER_SECTION = 'section';

    public const string SORT_VIEWS = 'views';

    public const string SORT_PER_VIEW = 'average_engaged';

    public const string SORT_SCROLL = 'average_scroll';

    /** Sort keys besides the engaged time (''). */
    public const array SORT_COLUMNS = [self::SORT_VIEWS, self::SORT_PER_VIEW, self::SORT_SCROLL];

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, string|list<string>>  $criteria
     * @return list<array<string, mixed>>
     */
    public function rows(array $rows, string $search = '', array $criteria = [], string $sort = '', string $direction = ListToolbar::SORT_DESC): array
    {
        $rows = $this->filtered($rows, $search, $criteria);

        if (in_array($sort, self::SORT_COLUMNS, true)) {
            usort($rows, fn (array $a, array $b): int => $direction === ListToolbar::SORT_ASC
                ? [$a[$sort], $a['path']] <=> [$b[$sort], $b['path']]
                : [$b[$sort], $a['path']] <=> [$a[$sort], $b['path']]);
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, string|list<string>>  $criteria
     */
    public function count(array $rows, string $search = '', array $criteria = []): int
    {
        return count($this->filtered($rows, $search, $criteria));
    }

    /**
     * Section => number of pages, for the views and the Section filter.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int>
     */
    public function sectionCounts(array $rows): array
    {
        return array_count_values(array_map(fn (array $row): string => (string) $row['section'], $rows));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, string|list<string>>  $criteria
     * @return list<array<string, mixed>>
     */
    private function filtered(array $rows, string $search, array $criteria): array
    {
        $term = mb_strtolower(trim($search));
        $sections = (array) ($criteria[self::FILTER_SECTION] ?? []);

        return array_values(array_filter($rows, fn (array $row): bool => ($term === '' || str_contains(mb_strtolower($row['label'].' '.$row['path']), $term))
            && ($sections === [] || in_array($row['section'], $sections, true))));
    }
}
