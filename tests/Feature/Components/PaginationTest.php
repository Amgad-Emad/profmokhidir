<?php

namespace Tests\Feature\Components;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    #[Test]
    public function it_renders_nothing_for_a_single_page(): void
    {
        $paginator = new LengthAwarePaginator(range(1, 5), 5, 20, 1, ['path' => '/publications']);

        $this->assertSame('', trim($paginator->links('components.pagination')->toHtml()));
    }

    #[Test]
    public function it_renders_previous_next_and_page_links_with_the_current_page_marked(): void
    {
        $paginator = (new LengthAwarePaginator(range(1, 20), 45, 20, 2, ['path' => '/publications']))->appends(['type' => 'book']);

        $html = $paginator->links('components.pagination')->toHtml();

        $this->assertStringContainsString('aria-label="Pagination"', $html);
        $this->assertStringContainsString('Page 2 of 3', $html);
        $this->assertStringContainsString('href="/publications?type=book&amp;page=1" class="filter-pill" rel="prev">Previous', $html);
        $this->assertStringContainsString('<span class="filter-pill" aria-current="page">2</span>', $html);
        $this->assertStringContainsString('href="/publications?type=book&amp;page=3" class="filter-pill" rel="next">Next', $html);
    }

    #[Test]
    public function the_ends_are_disabled_rather_than_hidden(): void
    {
        $first = (new LengthAwarePaginator(range(1, 20), 25, 20, 1, ['path' => '/p']))->links('components.pagination')->toHtml();
        $last = (new LengthAwarePaginator(range(21, 25), 25, 20, 2, ['path' => '/p']))->links('components.pagination')->toHtml();

        $this->assertStringContainsString('<span class="filter-pill is-disabled" aria-disabled="true">Previous</span>', $first);
        $this->assertStringContainsString('rel="next"', $first);
        $this->assertStringContainsString('<span class="filter-pill is-disabled" aria-disabled="true">Next</span>', $last);
        $this->assertStringContainsString('rel="prev"', $last);
    }

    #[Test]
    public function the_filter_pills_component_marks_the_active_option(): void
    {
        $html = Blade::render('<x-filter-pills label="Type" :options="$options" all-url="/p" :all-active="false" all-label="All types" />', [
            'options' => [
                ['label' => 'Book', 'url' => '/p?type=book', 'active' => true],
                ['label' => 'Report', 'url' => '/p?type=report', 'active' => false],
            ],
        ]);

        $this->assertStringContainsString('<nav aria-label="Type">', $html);
        $this->assertStringContainsString('<a href="/p" class="filter-pill">All types</a>', $html);
        $this->assertStringContainsString('<a href="/p?type=book" class="filter-pill" aria-current="true">Book</a>', $html);
        $this->assertStringContainsString('<a href="/p?type=report" class="filter-pill">Report</a>', $html);
    }
}
