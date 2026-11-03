{{--
    The region that holds an admin list under x-admin.list-toolbar (its tabs point here). While a
    search, filter, view, sort or page change is on its way it dims quietly (no spinner over the
    screen); `density` (comfortable or compact) sets the row spacing of the table or grid inside.
--}}
@props(['density' => \App\Constants\ListDensity::DEFAULT->value, 'id' => 'list-results'])
<div {{ $attributes->class(['list-results']) }} id="{{ $id }}" data-density="{{ $density }}"
     wire:loading.class="is-loading" wire:loading.attr="aria-busy"
     wire:target="search,filters,setFilter,toggleFilter,removeFilter,applyView,showTab,applyFilters,clearFilters,clearSearch,applySort,sortBy,gotoPage,nextPage,previousPage,setPerPage,perPage,showTrash,showActive" data-list-results>
    {{ $slot }}
</div>
