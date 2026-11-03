{{-- The footer of x-admin.data-table: "1–20 of 84", the page size and the pages (previous, numbers, next). --}}
@php
    $digits = app(\App\Services\LocaleService::class);
    $page = $table['page'];
    $pageName = $page->getPageName();
@endphp
<div class="data-table__footer" data-table-footer>
    <p class="data-table__range" aria-live="polite" data-table-range>{{ $table['summary']['label'] }}</p>

    <div class="data-table__per-page">
        <label for="{{ $table['id'] }}-per-page">{{ __('data-table.per_page') }}</label>
        <select id="{{ $table['id'] }}-per-page" class="data-table__select" wire:change="setPerPage($event.target.value)" data-per-page>
            @foreach ($table['pageSizes'] as $size)
                <option value="{{ $size }}" @selected($size === $table['perPage'])>{{ $digits->digits($size) }}</option>
            @endforeach
        </select>
    </div>

    @if ($page->hasPages())
        <nav class="data-table__pages" aria-label="{{ __('Pagination') }}">
            <ul>
                <li>
                    <button type="button" class="data-table__page" wire:click="previousPage('{{ $pageName }}')" @disabled($page->onFirstPage()) rel="prev" data-page-previous>
                        <x-icon name="chevron-left" class="size-4" /><span class="sr-only">{{ __('Previous') }}</span>
                    </button>
                </li>
                @foreach ($table['pages'] as $number)
                    <li>
                        @if ($number === null)
                            <span class="data-table__gap" aria-hidden="true">…</span>
                        @elseif ($number === $page->currentPage())
                            <span class="data-table__page" aria-current="page">{{ $digits->digits($number) }}<span class="sr-only"> {{ __('data-table.current_page') }}</span></span>
                        @else
                            <button type="button" class="data-table__page" wire:click="gotoPage({{ $number }}, '{{ $pageName }}')" aria-label="{{ __('Go to page :page', ['page' => $digits->digits($number)]) }}" data-page-number="{{ $number }}">{{ $digits->digits($number) }}</button>
                        @endif
                    </li>
                @endforeach
                <li>
                    <button type="button" class="data-table__page" wire:click="nextPage('{{ $pageName }}')" @disabled(! $page->hasMorePages()) rel="next" data-page-next>
                        <x-icon name="chevron-left" class="size-4 data-table__next-icon" /><span class="sr-only">{{ __('Next') }}</span>
                    </button>
                </li>
            </ul>
        </nav>
    @endif
</div>
