{{--
    One chapter's panel: numeral, name and strap on the left, the links with a line each in the
    middle, the featured item on the right. Opened by its button (click, Enter, Space or ArrowDown)
    or by hover on fine pointers; arrow keys move between the links, Escape closes and returns focus.
--}}
<div id="{{ $chapter['panelId'] }}" class="mega-menu" x-show="isOpen('{{ $chapter['key'] }}')" x-cloak x-transition:enter="mega-menu--moving" x-transition:enter-start="mega-menu--hidden" x-transition:leave="mega-menu--moving" x-transition:leave-end="mega-menu--hidden" aria-labelledby="{{ $chapter['buttonId'] }}" @keydown.down.prevent="moveFocus($event, 1)" @keydown.up.prevent="moveFocus($event, -1)" @keydown.home.prevent="moveFocus($event, 'first')" @keydown.end.prevent="moveFocus($event, 'last')">
    <div class="mega-menu__inner">
        <div class="mega-menu__title">
            <p class="t-numeral text-accent-text" aria-hidden="true">{{ $chapter['numeral'] }}</p>
            <p class="mega-menu__name"><span class="sr-only">Chapter {{ $chapter['numeral'] }}: </span>{{ $chapter['label'] }}</p>
            <p class="mega-menu__strap">{{ $chapter['strap'] }}</p>
        </div>
        <ul class="mega-menu__links">
            @foreach ($chapter['items'] as $item)
                <li>
                    <a href="{{ $item['url'] }}" class="mega-menu__link"@if ($item['active']) aria-current="page"@endif>
                        <span class="mega-menu__link-label">{{ $item['label'] }}</span>
                        <span class="mega-menu__link-description">{{ $item['description'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        @if ($chapter['featured'] !== null)
            @include('site.masthead.featured', ['featured' => $chapter['featured']])
        @endif
    </div>
</div>
