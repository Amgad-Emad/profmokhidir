{{--
    Full-screen menu for tablet and mobile: the search field at the top, the five chapters as an
    accordion table of contents, the tribute pill and the community link at the bottom. Focus is
    trapped inside while open; Escape closes and returns focus to the Menu button.
--}}
@use('App\Constants\Masthead')
@use('App\Constants\Search')
<div id="{{ Masthead::MENU_ID }}" x-ref="panel" x-show="open" x-cloak :class="{ 'is-open': shown }" role="dialog" aria-modal="true" aria-label="{{ Masthead::MENU_DIALOG_LABEL }}" tabindex="-1" @keydown.tab="trap($event)" class="site-menu glow-brass on-dark fixed inset-0 z-50 overflow-y-auto bg-surface text-ink lg:hidden">
    <div class="site-menu__bar mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
        <x-brand.lockup variant="horizontal" :href="route('home')" class="shrink-0" />
        <button type="button" x-ref="close" class="site-menu-toggle" @click="closeMenu()">
            <span class="t-label">{{ Masthead::CLOSE }}</span>
            <span class="site-menu-toggle__icon is-open" aria-hidden="true"><span></span><span></span></span>
        </button>
    </div>
    <div class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 sm:pt-10">
        <form method="get" action="{{ $searchUrl }}" role="search" class="site-menu__search">
            <label for="site-menu-search" class="sr-only">{{ Search::LABEL }}</label>
            <input id="site-menu-search" type="search" name="{{ $searchParam }}" placeholder="{{ Search::PLACEHOLDER }}" autocomplete="off" spellcheck="false" maxlength="{{ Search::MAX_LENGTH }}" class="site-menu__search-input">
            <button type="submit" class="site-menu__search-button">
                <svg class="site-search-toggle__icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true" focusable="false"><circle cx="8.5" cy="8.5" r="6" /><path d="m13 13 5.5 5.5" /></svg>
                <span class="sr-only">{{ Search::BUTTON_LABEL }}</span>
            </button>
        </form>

        <nav class="mt-10 sm:mt-14" aria-label="Site sections">
            <p class="t-label text-accent-text site-menu__caption">{{ Masthead::CONTENTS }}</p>
            <ol class="site-menu__list mt-6 flex flex-col">
                @foreach ($chapters as $index => $chapter)
                    <li class="site-menu__item" style="--i: {{ $index }}" x-data="{ expanded: {{ $chapter['active'] ? 'true' : 'false' }} }">
                        <button type="button" class="site-menu__chapter" aria-expanded="{{ $chapter['active'] ? 'true' : 'false' }}" :aria-expanded="expanded ? 'true' : 'false'" aria-controls="{{ \App\Constants\Masthead::mobileChapterPanelId($chapter['key']) }}"@if ($chapter['active']) aria-current="true"@endif @click="expanded = ! expanded">
                            <span class="site-menu__numeral t-numeral text-accent-text" aria-hidden="true">{{ $chapter['numeral'] }}</span>
                            <span class="site-menu__chapter-name"><span class="sr-only">Chapter {{ $chapter['numeral'] }}: </span>{{ $chapter['label'] }}</span>
                            <svg class="site-menu__chevron" :class="expanded && 'is-open'" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true" focusable="false"><path d="M1 3.5 5 7.5 9 3.5" /></svg>
                        </button>
                        <ul id="{{ \App\Constants\Masthead::mobileChapterPanelId($chapter['key']) }}" class="site-menu__links" x-show="expanded"@unless ($chapter['active']) x-cloak @endunless>
                            @foreach ($chapter['items'] as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" class="site-menu__link"@if ($item['active']) aria-current="page"@endif>
                                        <span class="site-menu__link-label">{{ $item['label'] }}</span>
                                        <span class="site-menu__link-description">{{ $item['description'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>
        </nav>

        <div class="site-menu__foot mt-12 flex flex-col items-start gap-6">
            <a href="{{ $tributeUrl }}" class="btn-pill-outline on-dark">{{ Masthead::LEAVE_TRIBUTE }}</a>
            <a href="{{ $utility['link']['url'] }}" class="link-draw smallcaps site-menu__utility">{{ $utility['link']['label'] }}</a>
        </div>
    </div>
</div>
