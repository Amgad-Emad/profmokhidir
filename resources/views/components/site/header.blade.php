{{--
    Fixed header. The glass bar, the full-screen menu and the search dialog are siblings:
    backdrop-filter would otherwise trap the fixed panels. The bar condenses (CSS height
    change on .is-condensed) once the page has scrolled; the active section carries a brass rule.
--}}
@use('App\Constants\Search')
<header class="site-header fixed inset-x-0 top-0 z-40" x-data="siteMenu" :class="{ 'is-condensed': condensed }" @keydown.escape.window="open && close()" @resize.window="onResize()" @scroll.window.passive="onScroll()">
    <div class="glass-header on-dark text-champagne">
        <div class="site-header__bar mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
            <x-brand.lockup variant="horizontal" label-tone="champagne" :href="route('home')" class="site-header__lockup shrink-0" />

            <nav class="hidden lg:block" aria-label="Primary">
                <ul class="flex items-center gap-6">
                    @foreach ($primary as $index => $item)
                        <li class="{{ $inlineClasses($index) }}">
                            <a href="{{ $item['url'] }}" class="nav-link t-label" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                        </li>
                    @endforeach

                    @if ($hasOverflow())
                        <li class="relative" x-data="{ more: false }" @click.outside="more = false" @focusout="if (! $el.contains($event.relatedTarget)) more = false" @keydown.escape.stop="more = false; $refs.moreButton.focus()">
                            <button type="button" x-ref="moreButton" class="nav-link t-label inline-flex items-center gap-2" aria-expanded="false" :aria-expanded="more ? 'true' : 'false'" aria-controls="nav-more" @click="more = ! more">
                                More
                                <svg class="h-2.5 w-2.5 transition-transform duration-300 motion-reduce:transition-none" :class="more && 'rotate-180'" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true" focusable="false"><path d="M1 3.5 5 7.5 9 3.5" /></svg>
                            </button>
                            <ul id="nav-more" x-show="more" x-cloak class="absolute right-0 top-full mt-5 min-w-60 rounded-card border border-cypress bg-evergreen p-2">
                                @foreach ($primary as $index => $item)
                                    <li class="{{ $overflowClasses($index) }}">
                                        <a href="{{ $item['url'] }}" class="nav-link nav-link--menu t-label block px-4 py-3" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                </ul>
            </nav>

            <div class="flex items-center gap-1 sm:gap-3">
                {{-- Opens the search dialog; without JavaScript it is a plain link to /search. --}}
                <a href="{{ $searchUrl }}" id="site-search-opener" class="site-search-toggle" aria-keyshortcuts="{{ $searchShortcuts }}" @click.prevent="$dispatch('search-open', { opener: $el })">
                    <svg class="site-search-toggle__icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true" focusable="false"><circle cx="8.5" cy="8.5" r="6" /><path d="m13 13 5.5 5.5" /></svg>
                    <span class="sr-only">{{ Search::TITLE }}</span><span class="t-label hidden sm:inline lg:hidden xl:inline" aria-hidden="true">{{ Search::TITLE }}</span>
                    <kbd class="site-search-toggle__key" aria-hidden="true">/</kbd>
                </a>

                <button type="button" x-ref="toggle" class="site-menu-toggle lg:hidden" aria-expanded="false" :aria-expanded="open ? 'true' : 'false'" aria-controls="site-menu" @click="toggle()">
                    <span class="t-label">Menu</span>
                    <span class="site-menu-toggle__icon" aria-hidden="true"><span></span><span></span></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Full-screen menu for tablet and mobile: the sections as a table of contents. Focus is trapped inside while open; Escape closes and returns focus to the button. --}}
    <div id="site-menu" x-ref="panel" x-show="open" x-cloak :class="{ 'is-open': shown }" role="dialog" aria-modal="true" aria-label="Site menu" tabindex="-1" @keydown.tab="trap($event)" class="site-menu glow-brass on-dark fixed inset-0 z-50 overflow-y-auto bg-evergreen text-champagne lg:hidden">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
            <x-brand.lockup variant="horizontal" :href="route('home')" class="shrink-0" />
            <button type="button" x-ref="close" class="site-menu-toggle" @click="close()">
                <span class="t-label">Close</span>
                <span class="site-menu-toggle__icon is-open" aria-hidden="true"><span></span><span></span></span>
            </button>
        </div>
        <nav class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14" aria-label="Site sections">
            <p class="t-label text-brass site-menu__caption">Contents</p>
            <ol class="site-menu__list mt-8 flex flex-col gap-2 sm:gap-4">
                @foreach ($all as $index => $item)
                    <li class="site-menu__item" style="--i: {{ $index }}">
                        <span class="site-menu__numeral t-numeral text-brass" aria-hidden="true">{{ $item['numeral'] }}</span>
                        <a href="{{ $item['url'] }}" class="site-menu__link" @if ($item['active']) aria-current="page" @endif><span class="sr-only">Chapter {{ $item['numeral'] }}: </span>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ol>
            <p class="mt-12">
                <a href="{{ $searchUrl }}" class="site-menu__search link-draw smallcaps">{{ Search::LABEL }}</a>
            </p>
        </nav>
    </div>

    {{-- Search dialog: evergreen, full screen. Results are fetched from /search as JSON and announced. --}}
    <div id="site-search" x-data="siteSearch" x-ref="panel" x-show="open" x-cloak :class="{ 'is-open': shown }" role="dialog" aria-modal="true" aria-labelledby="site-search-title" tabindex="-1" @search-open.window="show($event.detail?.opener)" @keydown.escape.stop="close()" @keydown.tab="trap($event)" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" class="site-search glow-brass on-dark fixed inset-0 z-50 overflow-y-auto bg-evergreen text-champagne">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
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
                @include('search.results', ['results' => ['groups' => [], 'summary' => ''], 'searchable' => false, 'sections' => $all, 'tone' => 'ivory', 'compact' => true])
            </div>
        </div>
    </div>
</header>
