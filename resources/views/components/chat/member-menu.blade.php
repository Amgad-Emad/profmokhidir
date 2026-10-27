{{--
    The member's menu in the chat header: a disclosure button with their initials in a hairline
    ring (and their name from 768px), opening their standing (member or administrator), name and affiliation, Account and Sign out.
    Escape and a click outside close it and return focus to the button.
--}}
@use('App\Constants\AdminNavigation')
@use('App\Constants\ChatCopy')
@props(['user'])
<div class="chat-member-menu" x-data="{ open: false }" x-on:keydown.escape.stop="if (open) { open = false; $refs.button.focus() }" x-on:click.outside="open = false" data-member-menu>
    <button type="button" class="chat-member-menu__button" x-ref="button" x-on:click="open = !open" aria-expanded="false" x-bind:aria-expanded="open.toString()" aria-controls="chat-member-menu">
        <span class="chat-avatar" aria-hidden="true">{{ \App\Support\Initials::of($user->name) }}</span>
        <span class="chat-member-menu__name" dir="auto">{{ $user->name }}</span>
        <span class="sr-only">, {{ mb_strtolower(__(ChatCopy::MEMBER_MENU_LABEL)) }}</span>
        <x-icon name="chevron-down" class="size-4" />
    </button>
    <div id="chat-member-menu" class="chat-member-menu__panel bg-surface-panel" x-show="open" x-cloak>
        <p class="chat-member-menu__who">
            <span class="t-label block text-accent-text">{{ $user->isAdmin() ? __(ChatCopy::STATUS_ADMIN) : __(ChatCopy::STATUS_MEMBER) }}</span>
            <span class="smallcaps mt-1 block text-lg text-ink" dir="auto">{{ $user->name }}</span>
            @if (filled($user->affiliation))
                <span class="t-label mt-1 block text-accent-text" dir="auto">{{ $user->affiliation }}</span>
            @endif
        </p>
        <ul>
            <li><a href="{{ route(AdminNavigation::ACCOUNT_ROUTE) }}" class="chat-member-menu__item"><x-icon name="user" class="size-4" />{{ __(ChatCopy::MENU_ACCOUNT) }}</a></li>
            <li>
                <x-account.sign-out item-class="chat-member-menu__item" />
            </li>
        </ul>
    </div>
</div>
