{{--
    The signed-in user's menu in the top bar: a disclosure button with the initials, opening the
    name and role, "Your account", "Keyboard shortcuts" and "Sign out". Escape and a click outside
    close it and return focus to the button.
--}}
@props(['user'])
@php $initials = \App\Support\Initials::of($user->name); @endphp
<div class="admin-user-menu" x-data="{ open: false }" x-on:keydown.escape="if (open) { open = false; $refs.button.focus() }" x-on:click.outside="open = false">
    <button type="button" class="admin-user-menu__button" x-ref="button" x-on:click="open = !open" aria-expanded="false" x-bind:aria-expanded="open.toString()" aria-controls="admin-user-menu" data-user-menu>
        <span class="admin-user-menu__initials" aria-hidden="true">{{ $initials }}</span>
        <span class="admin-user-menu__name">{{ $user->name }}</span>
        <span class="sr-only">, account menu</span>
        <x-icon name="chevron-down" class="size-4" />
    </button>
    <div id="admin-user-menu" class="admin-user-menu__panel" x-show="open" x-cloak>
        <p class="admin-user-menu__who">
            <span class="block font-medium text-ink">{{ $user->name }}</span>
            <span class="block text-sm font-light text-ink-muted">{{ $user->primaryRoleLabel() }}</span>
        </p>
        <ul>
            <li><a href="{{ route(\App\Constants\AdminNavigation::ACCOUNT_ROUTE) }}" class="admin-user-menu__item"><x-icon name="user" class="size-4" />Your account</a></li>
            <li><button type="button" class="admin-user-menu__item" x-on:click="open = false; $dispatch('admin-shortcuts-open')"><x-icon name="keyboard" class="size-4" />Keyboard shortcuts</button></li>
            <li>
                <x-account.sign-out item-class="admin-user-menu__item" />
            </li>
        </ul>
    </div>
</div>
