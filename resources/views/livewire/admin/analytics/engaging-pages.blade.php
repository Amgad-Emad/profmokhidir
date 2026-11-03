{{-- The "Most engaging pages" table of /admin/analytics with its list toolbar (App\Livewire\Admin\Analytics\EngagingPages). --}}
<div>
    <div class="analytics-panel__head">
        <h2 id="pages-heading" class="analytics-panel__title">{{ __('Most engaging pages') }}</h2>
        @if ($pages !== [])
            <x-button variant="quiet" size="sm" :href="$exportUrl" data-export="pages"><x-icon name="download" class="size-4" />CSV<span class="sr-only"> {{ __('of pages') }}</span></x-button>
        @endif
    </div>
    <p class="mt-2 max-w-2xl text-sm font-normal text-ink-muted">{{ __('Ranked by the time visitors spent reading: the tab in view and the visitor scrolling or interacting within the last :seconds seconds.', ['seconds' => app(\App\Services\LocaleService::class)->digits(\App\Constants\Analytics::IDLE_SECONDS)]) }}</p>

    <x-admin.list-toolbar :toolbar="$toolbar" :items="__('pages')" :placeholder="__('Search titles and addresses')" :table="$table" />

    <x-admin.list-results :density="$toolbar['density']">
        @if ($pages === [])
            @if ($toolbar['filtered'] && ! $sectionChosen)
                <x-admin.list-empty :title="__('Most engaging pages')" text="" :filtered="true" />
            @else
                <p class="text-sm font-normal text-ink-muted" data-pages-empty>{{ $sectionChosen ? __('No pages in this section were read in this period.') : __('No pages were read in this period.') }}</p>
            @endif
        @else
            <x-admin.data-table :table="$table" data-pages-table />
        @endif
    </x-admin.list-results>
</div>
