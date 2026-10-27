{{-- Brand pagination for LengthAwarePaginator: $paginator->links('components.pagination'). --}}
@if ($paginator->hasPages())
    <nav class="pagination mt-14 flex flex-col gap-6 border-t border-rule pt-8 sm:flex-row sm:items-center sm:justify-between" aria-label="{{ __('Pagination') }}">
        <p class="t-label text-accent-text">{{ __('Page :current of :last', ['current' => app(\App\Services\LocaleService::class)->digits($paginator->currentPage()), 'last' => app(\App\Services\LocaleService::class)->digits($paginator->lastPage())]) }}</p>
        <ul class="flex flex-wrap items-center gap-2">
            @if ($paginator->onFirstPage())
                <li><span class="filter-pill is-disabled" aria-disabled="true">{{ __('Previous') }}</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" class="filter-pill" rel="prev">{{ __('Previous') }}</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-2 text-ink-muted" aria-hidden="true">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="filter-pill" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="filter-pill" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" class="filter-pill" rel="next">{{ __('Next') }}</a></li>
            @else
                <li><span class="filter-pill is-disabled" aria-disabled="true">{{ __('Next') }}</span></li>
            @endif
        </ul>
    </nav>
@endif
