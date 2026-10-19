{{-- Livewire-aware pagination in the brand style: $items->links('components.admin.pagination'). --}}
@php $pageName = $paginator->getPageName(); @endphp
@if ($paginator->hasPages())
    <nav class="pagination mt-10 flex flex-col gap-6 border-t border-stone pt-6 sm:flex-row sm:items-center sm:justify-between" aria-label="Pagination">
        <p class="t-label text-bronze">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }} · {{ $paginator->total() }} {{ \Illuminate\Support\Str::plural('item', $paginator->total()) }}</p>
        <ul class="flex flex-wrap items-center gap-2">
            @if ($paginator->onFirstPage())
                <li><span class="filter-pill is-disabled" aria-disabled="true">Previous</span></li>
            @else
                <li><button type="button" class="filter-pill" wire:click="previousPage('{{ $pageName }}')" rel="prev">Previous</button></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-2 text-ash" aria-hidden="true">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="filter-pill" aria-current="page">{{ $page }}</span>
                            @else
                                <button type="button" class="filter-pill" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" aria-label="Go to page {{ $page }}">{{ $page }}</button>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><button type="button" class="filter-pill" wire:click="nextPage('{{ $pageName }}')" rel="next">Next</button></li>
            @else
                <li><span class="filter-pill is-disabled" aria-disabled="true">Next</span></li>
            @endif
        </ul>
    </nav>
@endif
