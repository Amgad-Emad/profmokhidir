{{--
    Pagination styled as a book: "Page iii of xii" in small caps between previous and next.
    Plain links, so the pages work without JavaScript. Use with a LengthAwarePaginator:
    {{ $paginator->links('components.book-pagination') }}
--}}
@inject('typesetter', 'App\Services\TypesetterService')
@if ($paginator->hasPages())
    <nav class="book-pagination" aria-label="Pages of the book">
        @if ($paginator->onFirstPage())
            <span class="book-pagination__link smallcaps is-disabled" aria-disabled="true">Previous page</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="book-pagination__link smallcaps" rel="prev">Previous page</a>
        @endif

        <p class="book-pagination__folio smallcaps">
            <span class="sr-only">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
            <span aria-hidden="true">Page {{ $typesetter->roman($paginator->currentPage()) }} of {{ $typesetter->roman($paginator->lastPage()) }}</span>
        </p>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="book-pagination__link book-pagination__link--next smallcaps" rel="next">Next page</a>
        @else
            <span class="book-pagination__link book-pagination__link--next smallcaps is-disabled" aria-disabled="true">Next page</span>
        @endif
    </nav>
@endif
