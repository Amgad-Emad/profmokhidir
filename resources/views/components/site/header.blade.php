{{-- Fixed header. The glass bar and the full-screen menu are siblings: backdrop-filter would otherwise trap the fixed menu. --}}
<header class="site-header fixed inset-x-0 top-0 z-40" x-data="siteMenu" @keydown.escape.window="open && close()" @resize.window="onResize()">
    <div class="glass-header on-dark text-champagne">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
            <x-brand.lockup variant="horizontal" label-tone="champagne" :href="route('home')" class="shrink-0" />

            <nav class="hidden lg:block" aria-label="Primary">
                <ul class="flex items-center gap-6">
                    @foreach ($primary as $index => $item)
                        <li class="{{ $inlineClasses($index) }}">
                            <a href="{{ $item['url'] }}" class="nav-link t-label" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                        </li>
                    @endforeach

                    @if ($hasOverflow())
                        <li class="relative 2xl:hidden" x-data="{ more: false }" @click.outside="more = false" @focusout="if (! $el.contains($event.relatedTarget)) more = false" @keydown.escape.stop="more = false; $refs.moreButton.focus()">
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

            <button type="button" x-ref="toggle" class="site-menu-toggle lg:hidden" aria-expanded="false" :aria-expanded="open ? 'true' : 'false'" aria-controls="site-menu" @click="toggle()">
                <span class="t-label">Menu</span>
                <span class="site-menu-toggle__icon" aria-hidden="true"><span></span><span></span></span>
            </button>
        </div>
    </div>

    {{-- Full-screen menu for tablet and mobile. Focus is trapped inside while open; Escape closes and returns focus to the button. --}}
    <div id="site-menu" x-ref="panel" x-show="open" x-cloak :class="{ 'is-open': shown }" role="dialog" aria-modal="true" aria-label="Site menu" tabindex="-1" @keydown.tab="trap($event)" class="site-menu glow-brass on-dark fixed inset-0 z-50 overflow-y-auto bg-evergreen text-champagne lg:hidden">
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6">
            <x-brand.lockup variant="horizontal" :href="route('home')" class="shrink-0" />
            <button type="button" x-ref="close" class="site-menu-toggle" @click="close()">
                <span class="t-label">Close</span>
                <span class="site-menu-toggle__icon is-open" aria-hidden="true"><span></span><span></span></span>
            </button>
        </div>
        <nav class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14" aria-label="Site sections">
            <ul class="site-menu__list flex flex-col gap-2 sm:gap-4">
                @foreach ($all as $index => $item)
                    <li class="site-menu__item" style="--i: {{ $index }}">
                        <a href="{{ $item['url'] }}" class="site-menu__link" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</header>
