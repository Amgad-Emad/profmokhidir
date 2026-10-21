<?php

namespace Tests\Feature\Components;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookPaginationTest extends TestCase
{
    private function render(int $page, int $total, int $perPage = 1): string
    {
        $paginator = new LengthAwarePaginator(range(1, $perPage), $total, $perPage, $page, ['path' => 'https://profmokhidir.test/tributes']);
        $paginator->appends(['relationship' => 'student']);

        return View::make('components.book-pagination', ['paginator' => $paginator, 'elements' => []])->render();
    }

    #[Test]
    public function it_reads_page_iii_of_xii_in_small_caps_with_plain_previous_and_next_links(): void
    {
        $html = $this->render(3, 12);

        $this->assertStringContainsString('<nav class="book-pagination" aria-label="Pages of the book">', $html);
        $this->assertStringContainsString('<span class="sr-only">Page 3 of 12</span>', $html);
        $this->assertStringContainsString('<span aria-hidden="true">Page III of XII</span>', $html);
        $this->assertStringContainsString('class="book-pagination__folio smallcaps"', $html);
        $this->assertStringContainsString('href="https://profmokhidir.test/tributes?relationship=student&amp;page=2" class="book-pagination__link smallcaps" rel="prev">Previous page</a>', $html);
        $this->assertStringContainsString('href="https://profmokhidir.test/tributes?relationship=student&amp;page=4" class="book-pagination__link book-pagination__link--next smallcaps" rel="next">Next page</a>', $html);
        $this->assertStringNotContainsString('x-data', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    #[Test]
    public function the_first_and_last_pages_disable_the_missing_direction(): void
    {
        $first = $this->render(1, 2);
        $this->assertStringContainsString('<span class="book-pagination__link smallcaps is-disabled" aria-disabled="true">Previous page</span>', $first);
        $this->assertStringContainsString('rel="next"', $first);

        $last = $this->render(2, 2);
        $this->assertStringContainsString('rel="prev"', $last);
        $this->assertStringContainsString('<span class="book-pagination__link book-pagination__link--next smallcaps is-disabled" aria-disabled="true">Next page</span>', $last);
    }

    #[Test]
    public function a_single_page_renders_nothing(): void
    {
        $this->assertSame('', trim($this->render(1, 1)));
    }
}
