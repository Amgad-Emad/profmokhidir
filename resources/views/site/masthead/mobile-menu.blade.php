{{--
    Full-screen menu for tablet and mobile: the search field at the top, the five chapters as an
    accordion table of contents, the tribute pill and the community link at the bottom. Focus is
    trapped inside while open; Escape closes and returns focus to the Menu button.
--}}
@use('App\Constants\Masthead')
@use('App\Constants\Search')
<div id="{{ Masthead::MENU_ID }}" x-ref="panel" x-show="open" x-cloak :class="{ 'is-open': shown }" role="dialog" aria-modal="true" aria-label="{{ __(Masthead::MENU_DIALOG_LABEL) }}" tabindex="-1" @keydown.tab="trap($event)" class="site-menu glow-brass on-dark fixed inset-0 z-50 overflow-y-auto bg-surface text-ink lg:hidden">
    <div class="site-menu__bar mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
        <x-brand.lockup variant="horizontal" :href="route('home')" class="shrink-0" />
        <x-locale.switcher variant="masthead" class="site-menu__locale ms-auto" />
        <button type="button" x-ref="close" class="site-menu-toggle" @click="closeMenu()">
            <span class="t-label">{{ __(Masthead::CLOSE) }}</span>
            <span class="site-menu-toggle__icon is-open" aria-hidden="true"><span></span><span></span></span>
        </button>
    </div>
    <div class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 sm:pt-10">
        <form novalidate method="get" action="{{ $searchUrl }}" role="search" class="site-menu__search">
            <x-form.input type="search" :name="$searchParam" id="site-menu-search" :label="__(Search::LABEL)" hide-label :optional="false" class="form-style-underline min-w-0 flex-1" control-class="site-menu__search-input"
                          :placeholder="__(Search::PLACEHOLDER)" autocomplete="off" spellcheck="false" :maxlength="Search::MAX_LENGTH" />
            <button type="submit" class="site-menu__search-button">
                <svg class="site-search-toggle__icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true" focusable="false"><circle cx="8.5" cy="8.5" r="6" /><path d="m13 13 5.5 5.5" /></svg>
                <span class="sr-only">{{ __(Search::BUTTON_LABEL) }}</span>
            </button>
        </form>

        <nav class="mt-10 sm:mt-14" aria-label="{{ __('Site sections') }}">
            <p class="t-label text-accent-text site-menu__caption">{{ __(Masthead::CONTENTS) }}</p>
            <ol class="site-menu__list mt-6 flex flex-col">
                @foreach ($chapters as $index => $chapter)
                    <li class="site-menu__item" style="--i: {{ $index }}" x-data="{ expanded: {{ $chapter['active'] ? 'true' : 'false' }} }">
                        <button type="button" class="site-menu__chapter" aria-expanded="{{ $chapter['active'] ? 'true' : 'false' }}" :aria-expanded="expanded ? 'true' : 'false'" aria-controls="{{ \App\Constants\Masthead::mobileChapterPanelId($chapter['key']) }}"@if ($chapter['active']) aria-current="true"@endif @click="expanded = ! expanded">
                            <span class="site-menu__numeral t-numeral text-accent-text" aria-hidden="true">{{ $chapter['numeral'] }}</span>
                            <span class="site-menu__chapter-name"><span class="sr-only">{{ __('Chapter :numeral:', ['numeral' => $chapter['numeral']]) }} </span>{{ $chapter['label'] }}</span>
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
            <x-button on-dark :href="$tributeUrl">{{ $tributeLabel }}</x-button>
            <a href="{{ $utility['link']['url'] }}" class="link-draw smallcaps site-menu__utility">{{ $utility['link']['label'] }}</a>
            @if ($account !== null)
                <x-account.sign-out variant="quiet" data-mobile-sign-out />
            @endif
        </div>
    </div>
</div>
