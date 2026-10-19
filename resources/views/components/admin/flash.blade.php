{{-- Outcome of the last action on an admin screen (set with session()->flash('admin.status', ...)). --}}
@if (session('admin.status'))
    <x-alert tone="cypress" class="mb-6" data-flash>{{ session('admin.status') }}</x-alert>
@endif
