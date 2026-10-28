<?php

namespace Tests\Unit\Services;

use App\Constants\ListToolbar;
use App\Services\AnalyticsPagesListService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The analytics page list: rows from AnalyticsReportService::pageRows, searched, filtered by section and sorted.
 */
class AnalyticsPagesListServiceTest extends TestCase
{
    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            ['path' => '/publications', 'label' => 'Publications', 'section' => 'publications', 'views' => 50, 'engaged_seconds' => 900, 'average_engaged' => 18.0, 'average_scroll' => 40.0],
            ['path' => '/commentary/on-soil', 'label' => 'On soil', 'section' => 'commentary', 'views' => 20, 'engaged_seconds' => 600, 'average_engaged' => 30.0, 'average_scroll' => 80.0],
            ['path' => '/commentary', 'label' => 'Commentary Corner', 'section' => 'commentary', 'views' => 70, 'engaged_seconds' => 300, 'average_engaged' => 4.3, 'average_scroll' => 55.0],
        ];
    }

    #[Test]
    public function rows_are_searched_filtered_and_sorted(): void
    {
        $list = new AnalyticsPagesListService;
        $paths = fn (array $rows): array => array_column($rows, 'path');

        $this->assertSame(['/publications', '/commentary/on-soil', '/commentary'], $paths($list->rows($this->rows())));
        $this->assertSame(['/commentary/on-soil', '/commentary'], $paths($list->rows($this->rows(), '', [AnalyticsPagesListService::FILTER_SECTION => ['commentary']])));
        $this->assertSame(['/commentary/on-soil'], $paths($list->rows($this->rows(), 'SOIL')));
        $this->assertSame(['/commentary', '/publications', '/commentary/on-soil'], $paths($list->rows($this->rows(), '', [], AnalyticsPagesListService::SORT_VIEWS)));
        $this->assertSame(['/commentary/on-soil', '/publications', '/commentary'], $paths($list->rows($this->rows(), '', [], AnalyticsPagesListService::SORT_PER_VIEW)));
        $this->assertSame(['/publications', '/commentary', '/commentary/on-soil'], $paths($list->rows($this->rows(), '', [], AnalyticsPagesListService::SORT_SCROLL, ListToolbar::SORT_ASC)));
        $this->assertSame(2, $list->count($this->rows(), 'comm'));
        $this->assertSame(['publications' => 1, 'commentary' => 2], $list->sectionCounts($this->rows()));
    }
}
