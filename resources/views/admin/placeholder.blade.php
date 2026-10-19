<x-layouts.admin :title="$item['label']">
    <header class="flex flex-col gap-2">
        <p class="t-label text-bronze">{{ $group }}</p>
        <h1 class="t-heading">{{ $item['label'] }}</h1>
    </header>
    <x-empty-state message="This section is on its way. Its screens will appear here once they are built." />
    <x-button variant="outline" :href="route(\App\Constants\AdminNavigation::DASHBOARD_ROUTE)">Back to the dashboard</x-button>
</x-layouts.admin>
