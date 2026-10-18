<x-layouts.admin title="Dashboard">
    <p class="t-label text-bronze">Dashboard</p>
    <h1 class="mt-3 text-heading">Welcome, {{ auth()->user()->name }}</h1>
    <p class="mt-4 max-w-2xl text-ash">Content sections will be added here step by step. Signed in as <span class="font-medium text-evergreen">{{ \App\Constants\Roles::labels()[auth()->user()->role] ?? auth()->user()->role }}</span>.</p>
</x-layouts.admin>
