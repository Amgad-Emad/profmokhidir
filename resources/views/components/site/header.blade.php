{{--
    The masthead. The glass bar, the full-screen menu and the search dialog are siblings:
    backdrop-filter would otherwise trap the fixed panels. resources/js/masthead.js (the Alpine
    component `masthead`) compacts the bar as the reader scrolls down and restores it on the
    way up, lets it go transparent over the home hero, and runs the five chapter disclosures.
    Pass `hero` on pages that open with the evergreen hero under the bar.
--}}
@use('App\Constants\Masthead')
@use('App\Constants\Search')
<header class="site-header fixed inset-x-0 top-0 z-40" x-data="masthead" :class="{ 'is-compact': compact, 'is-over-hero': overHero, 'has-chapter-open': openChapter !== null }" @keydown.escape.window="onEscape()" @resize.window="onResize()" @scroll.window.passive="onScroll()">
    <div class="glass-header on-dark text-ink">
        @include('site.masthead.utility-line')
        <div class="site-header__bar mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 sm:gap-6 sm:px-6 lg:gap-10">
            <x-brand.lockup variant="horizontal" label-tone="champagne" :href="route('home')" class="site-header__lockup shrink-0" />

            <nav class="site-chapters hidden lg:block" aria-label="{{ __(Masthead::NAV_LABEL) }}" x-ref="chapters">
                <ul class="flex items-center gap-8 xl:gap-10">
                    @foreach ($chapters as $chapter)
                        <li class="site-chapter" data-chapter="{{ $chapter['key'] }}" @mouseenter="hoverOpen('{{ $chapter['key'] }}')" @mouseleave="hoverClose('{{ $chapter['key'] }}')" @focusout="onFocusOut($event, '{{ $chapter['key'] }}')" @click.outside="onClickOutside('{{ $chapter['key'] }}')">
                            <button type="button" id="{{ $chapter['buttonId'] }}" class="chapter-link t-label" aria-expanded="false" :aria-expanded="isOpen('{{ $chapter['key'] }}') ? 'true' : 'false'" aria-controls="{{ $chapter['panelId'] }}"@if ($chapter['active']) aria-current="true"@endif @click="toggleChapter('{{ $chapter['key'] }}')" @keydown.down.prevent="openAndFocus('{{ $chapter['key'] }}')" @keydown.right.prevent="focusSibling($event, 1)" @keydown.left.prevent="focusSibling($event, -1)">{{ $chapter['label'] }}</button>
                            @include('site.masthead.mega-menu', ['chapter' => $chapter])
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="flex items-center gap-1 sm:gap-3">
                {{-- Opens the search dialog; without JavaScript it is a plain link to /search. Phones search from the top of the full-screen menu instead. --}}
                <a href="{{ $searchUrl }}" id="{{ Masthead::SEARCH_OPENER_ID }}" class="site-search-toggle max-sm:hidden" aria-keyshortcuts="{{ $searchShortcuts }}" @click.prevent="$dispatch('search-open', { opener: $el })">
                    <svg class="site-search-toggle__icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true" focusable="false"><circle cx="8.5" cy="8.5" r="6" /><path d="m13 13 5.5 5.5" /></svg>
                    <span class="sr-only">{{ __(Search::TITLE) }}</span>
                    <kbd class="site-search-toggle__key" aria-hidden="true">/</kbd>
                </a>

                @if ($tributeUrl !== null)
                    <x-button size="sm" :href="$tributeUrl" class="site-header__tribute hidden xl:inline-flex">{{ $tributeLabel }}</x-button>
                @endif

                <x-locale.switcher variant="masthead" class="site-header__locale max-sm:hidden" />

                <x-theme.switch id="theme-switch-masthead" :class="$account !== null ? 'site-header__theme site-header__theme--in-menu' : 'site-header__theme'" />

                @include('site.masthead.account-menu')

                <button type="button" x-ref="toggle" class="site-menu-toggle lg:hidden" aria-expanded="false" :aria-expanded="open ? 'true' : 'false'" aria-controls="{{ Masthead::MENU_ID }}" @click="toggleMenu()">
                    <span class="t-label max-sm:sr-only">{{ __(Masthead::MENU) }}</span>
                    <span class="site-menu-toggle__icon" aria-hidden="true"><span></span><span></span></span>
                </button>
            </div>
        </div>
    </div>

    @include('site.masthead.mobile-menu')
    @include('site.masthead.search-dialog')
</header>
