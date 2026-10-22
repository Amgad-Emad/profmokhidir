{{--
    Dashboard navigation on evergreen: horizontal lockup, label-style group headings and
    one link per item the user may see. The active item carries a brass rule on its left.
    Driven by App\Constants\AdminNavigation.
--}}
@php
    use App\Constants\AdminNavigation;

    $user = auth()->user();
    $current = request()->route()?->getName() ?? '';
@endphp
<nav class="flex h-full flex-col" aria-label="Dashboard">
    <div class="border-b border-cypress px-6 py-5">
        <x-brand.lockup variant="horizontal" tone="ivory" :href="route(AdminNavigation::DASHBOARD_ROUTE)" class="text-champagne" compact />
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-6">
        @php $dashboardActive = $current === AdminNavigation::DASHBOARD_ROUTE; @endphp
        <a href="{{ route(AdminNavigation::DASHBOARD_ROUTE) }}" class="admin-nav-link{{ $dashboardActive ? ' admin-nav-link--active' : '' }}"{!! $dashboardActive ? ' aria-current="page"' : '' !!} data-nav-item="dashboard">Dashboard</a>

        @foreach (AdminNavigation::groups() as $groupKey => $groupLabel)
            @php
                $items = array_filter(AdminNavigation::itemsIn($groupKey), fn (array $item): bool => $user->can($item['permission']));
            @endphp
            @if ($items !== [])
                <div class="mt-8" data-nav-group="{{ $groupKey }}">
                    <p class="t-label px-3 text-brass">{{ $groupLabel }}</p>
                    <ul class="mt-2">
                        @foreach ($items as $key => $item)
                            @php $active = $current === $item['route'] || str_starts_with($current, $item['route'].'.'); @endphp
                            <li>
                                <a href="{{ route($item['route']) }}" class="admin-nav-link{{ $active ? ' admin-nav-link--active' : '' }}"{!! $active ? ' aria-current="page"' : '' !!} data-nav-item="{{ $key }}">{{ $item['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>

    <div class="border-t border-cypress px-6 py-5 text-sm">
        <p class="font-medium text-ivory">{{ $user->name }}</p>
        <p class="font-light text-champagne">{{ $user->primaryRoleLabel() }}</p>
        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2">
            <a href="{{ route('home') }}" class="text-champagne underline-offset-4 hover:text-ivory hover:underline">View site</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-champagne underline-offset-4 hover:text-ivory hover:underline">Sign out</button>
            </form>
        </div>
    </div>
</nav>
