{{-- Search dialog: evergreen, full screen. Results are fetched from /search as JSON and announced. --}}
@use('App\Constants\Masthead')
@use('App\Constants\Search')
<div id="{{ Masthead::SEARCH_ID }}" x-data="siteSearch" x-ref="panel" x-show="open" x-cloak :class="{ 'is-open': shown }" role="dialog" aria-modal="true" aria-labelledby="site-search-title" tabindex="-1" @search-open.window="show($event.detail?.opener)" @keydown.escape.stop="close()" @keydown.tab="trap($event)" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" class="site-search glow-brass on-dark fixed inset-0 z-50 overflow-y-auto bg-evergreen text-champagne">
    <div class="site-menu__bar mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
        <p id="site-search-title" class="t-label text-brass">{{ Search::LABEL }}</p>
        <button type="button" x-ref="close" class="site-menu-toggle" @click="close()">
            <span class="t-label">{{ Search::CLOSE_LABEL }}</span>
            <span class="site-menu-toggle__icon is-open" aria-hidden="true"><span></span><span></span></span>
        </button>
    </div>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-12">
        <form x-ref="form" method="get" action="{{ $searchUrl }}" role="search" class="site-search__form" @submit="submit($event)">
            <label for="site-search-input" class="sr-only">{{ Search::LABEL }}</label>
            <input x-ref="input" id="site-search-input" type="search" name="{{ $searchParam }}" x-model="query" @input.debounce.250ms="lookup()" placeholder="{{ Search::PLACEHOLDER }}" autocomplete="off" spellcheck="false" maxlength="{{ Search::MAX_LENGTH }}" aria-controls="site-search-results" aria-describedby="site-search-hint" class="site-search__input">
            <p id="site-search-hint" class="site-search__hint smallcaps">Enter for every result<span aria-hidden="true">{{ \App\Constants\DateFormats::META_SEPARATOR }}</span><span class="sr-only">, </span>Escape to close</p>
        </form>

        <p class="sr-only" role="status" aria-live="polite" aria-atomic="true" x-text="status"></p>

        <div class="site-search__loading mt-10" x-show="loading" x-cloak>
            <x-skeleton :lines="4" tone="ivory" />
        </div>

        <div id="site-search-results" x-ref="results" class="site-search__results search-results mt-10" :class="{ 'is-loading': loading }">
            @include('search.results', ['results' => ['groups' => [], 'summary' => ''], 'searchable' => false, 'sections' => $sections, 'tone' => 'ivory', 'compact' => true])
        </div>
    </div>
</div>
