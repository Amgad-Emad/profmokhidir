{{--
    Dashboard navigation on the dark band (an on-dark region): the horizontal lockup, the dashboard
    link, then one collapsible group per AdminNavigation group (Content, Media, Community, Tributes,
    Settings) with the items the user may see. Items that need attention carry a count; the active
    item carries a brass rule on its left and aria-current. On desktop the whole sidebar collapses
    to icons (adminShell in resources/js/admin/shell.js remembers the choice); labels stay in the
    accessibility tree. `nav` comes from AdminShellService::sidebar().
--}}
@props(['nav', 'user'])
<nav class="admin-nav flex h-full flex-col" aria-label="Dashboard">
    <div class="admin-nav__brand border-b border-rule">
        <x-brand.lockup variant="horizontal" tone="ivory" :href="route(\App\Constants\AdminNavigation::DASHBOARD_ROUTE)" class="admin-nav__lockup text-ink" compact />
        <a href="{{ route(\App\Constants\AdminNavigation::DASHBOARD_ROUTE) }}" class="admin-nav__mark" aria-hidden="true" tabindex="-1">
            <x-brand.mark :size="28" decorative />
        </a>
    </div>

    <div class="admin-nav__scroll flex-1 overflow-y-auto px-3 py-6">
        @php $dashboard = $nav['dashboard']; @endphp
        <a href="{{ $dashboard['url'] }}" class="admin-nav-link{{ $dashboard['active'] ? ' admin-nav-link--active' : '' }}"{!! $dashboard['active'] ? ' aria-current="page"' : '' !!} data-nav-item="dashboard" title="{{ $dashboard['label'] }}">
            <x-icon :name="$dashboard['icon']" class="admin-nav-link__icon size-5" />
            <span class="admin-nav-link__label">{{ $dashboard['label'] }}</span>
        </a>

        @foreach ($nav['groups'] as $group)
            @php $hasActive = collect($group['items'])->contains('active', true); @endphp
            <div class="admin-nav__group" data-nav-group="{{ $group['key'] }}" x-data="{ open: groupOpen('{{ $group['key'] }}', {{ $hasActive ? 'true' : 'false' }}) }">
                <h2 class="admin-nav__heading">
                    <button type="button" class="admin-nav__toggle" x-on:click="open = !open; rememberGroup('{{ $group['key'] }}', open)"
                            aria-expanded="true" x-bind:aria-expanded="open.toString()" aria-controls="nav-group-{{ $group['key'] }}">
                        <span class="t-label">{{ $group['label'] }}</span>
                        @if ($group['count'] > 0)
                            <span class="admin-count admin-count--quiet" x-show="!open" x-cloak>{{ $group['count'] }}<span class="sr-only"> waiting</span></span>
                        @endif
                        <x-icon name="chevron-down" class="admin-nav__chevron size-4" />
                    </button>
                </h2>
                <ul id="nav-group-{{ $group['key'] }}" class="admin-nav__list" x-show="open || collapsed">
                    @foreach ($group['items'] as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="admin-nav-link{{ $item['active'] ? ' admin-nav-link--active' : '' }}"{!! $item['active'] ? ' aria-current="page"' : '' !!} data-nav-item="{{ $item['key'] }}" title="{{ $item['label'] }}">
                                <x-icon :name="$item['icon']" class="admin-nav-link__icon size-5" />
                                <span class="admin-nav-link__label">{{ $item['label'] }}</span>
                                @if ($item['count'] > 0)
                                    <span class="admin-count" data-nav-count="{{ $item['key'] }}">{{ $item['count'] }}<span class="sr-only"> waiting</span></span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="admin-nav__foot border-t border-rule">
        <p class="admin-nav__who text-sm">
            <span class="block font-medium text-ink-strong">{{ $user->name }}</span>
            <span class="block font-normal text-ink">{{ $user->primaryRoleLabel() }}</span>
        </p>
        <x-account.sign-out item-class="admin-nav-link admin-nav__sign-out" data-sidebar-sign-out />
        <button type="button" class="admin-nav__collapse" x-on:click="toggleCollapsed()" x-bind:aria-pressed="collapsed.toString()" aria-pressed="false">
            <x-icon name="collapse" class="admin-nav__collapse-icon size-5" />
            <span class="admin-nav-link__label">Collapse to icons</span>
        </button>
    </div>
</nav>
