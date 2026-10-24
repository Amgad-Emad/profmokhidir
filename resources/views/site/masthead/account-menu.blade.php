{{--
    The account menu in the masthead bar. Signed in: a disclosure button with the initials in a
    hairline ring (and the name from 1280px), opening the name and role, Your account, Community
    chat (approved members and admins), Dashboard (staff), the theme toggle and Sign out. Escape
    and a click outside close it and return focus to the button. Signed out: a quiet "Sign in" link.
--}}
@use('App\Constants\Masthead')
@if ($account === null)
    <a href="{{ $signInUrl }}" class="site-account-signin link-draw t-label hidden sm:inline-flex" data-sign-in-link>{{ Masthead::SIGN_IN_SHORT }}</a>
@else
    <div class="site-account" x-data="{ open: false }" x-on:keydown.escape.stop="if (open) { open = false; $refs.button.focus() }" x-on:click.outside="open = false" data-account-menu>
        <button type="button" class="site-account__button" x-ref="button" x-on:click="open = !open" aria-expanded="false" x-bind:aria-expanded="open.toString()" aria-controls="{{ Masthead::ACCOUNT_MENU_ID }}">
            <span class="site-account__avatar" aria-hidden="true">{{ $account['initials'] }}</span>
            <span class="site-account__name">{{ $account['name'] }}</span>
            <span class="sr-only">, {{ mb_strtolower(Masthead::ACCOUNT_MENU_LABEL) }}</span>
            <x-icon name="chevron-down" class="site-account__chevron size-4 max-sm:hidden" />
        </button>
        <div id="{{ Masthead::ACCOUNT_MENU_ID }}" class="site-account__panel bg-surface-panel" x-show="open" x-cloak>
            <p class="site-account__who">
                <span class="t-label block text-accent-text">{{ $account['role'] }}</span>
                <span class="mt-1 block text-lg text-ink-strong">{{ $account['name'] }}</span>
            </p>
            <ul class="site-account__list">
                @foreach ($account['items'] as $item)
                    <li><a href="{{ $item['url'] }}" class="site-account__item" data-account-item="{{ $item['key'] }}"><x-icon :name="$item['icon']" class="size-4" />{{ $item['label'] }}</a></li>
                @endforeach
                <li class="site-account__theme">
                    <span class="site-account__theme-label">{{ Masthead::ACCOUNT_MENU_THEME }}</span>
                    <x-theme.switch id="theme-switch-account-menu" />
                </li>
                <li><x-account.sign-out item-class="site-account__item" /></li>
            </ul>
        </div>
    </div>
@endif
