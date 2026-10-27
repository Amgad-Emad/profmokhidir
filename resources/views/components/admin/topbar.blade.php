{{--
    The admin top bar on the panel surface: the menu button (below lg), the breadcrumb trail, the
    command palette button (Ctrl/Cmd+K), the theme switch, "View site" and the user menu. The
    breadcrumbs come from AdminShellService::breadcrumbs(); the last one is the current screen.
--}}
@props(['crumbs', 'user'])
<header class="admin-topbar" data-admin-topbar>
    <div class="admin-topbar__row">
        <x-button variant="icon" type="button" class="lg:hidden"
                x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-expanded="false" aria-controls="admin-sidebar">
            <span class="sr-only" x-text="open ? @js(__('Close menu')) : @js(__('Open menu'))">{{ __('Open menu') }}</span>
            <x-icon name="menu" x-show="!open" />
            <x-icon name="close" x-show="open" x-cloak />
        </x-button>

        <nav class="admin-breadcrumbs" aria-label="{{ __('Breadcrumb') }}" data-admin-breadcrumbs>
            <ol>
                @foreach ($crumbs as $crumb)
                    <li @class(['admin-breadcrumbs__item', 'admin-breadcrumbs__item--early' => ! $loop->last && $loop->remaining > 1])>
                        @if ($crumb['url'] && ! $loop->last)
                            <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                        @elseif ($loop->last)
                            <span aria-current="page">{{ $crumb['label'] }}</span>
                        @else
                            <span>{{ $crumb['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>

        <div class="admin-topbar__tools">
            <button type="button" class="admin-search-button" x-on:click="$dispatch('admin-palette-open')" aria-haspopup="dialog" data-palette-open>
                <x-icon name="search" class="size-4" />
                <span class="admin-search-button__label">{{ __('Search or jump to…') }}</span>
                <kbd class="admin-kbd" data-shortcut-modifier>Ctrl</kbd><kbd class="admin-kbd">K</kbd>
                <span class="sr-only">{{ __('(keyboard shortcut Control or Command K)') }}</span>
            </button>
            <x-locale.switcher variant="toolbar" />
            <x-theme.switch id="theme-switch-admin" />
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="admin-topbar__link" data-view-site>
                <x-icon name="external" class="size-4" />
                <span class="admin-topbar__link-label">{{ __('View site') }}</span><span class="sr-only"> {{ __('(opens in a new tab)') }}</span>
            </a>
            <x-admin.user-menu :user="$user" />
        </div>
    </div>
</header>
