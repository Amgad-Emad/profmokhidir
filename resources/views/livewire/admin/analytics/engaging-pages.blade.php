{{-- The "Most engaging pages" table of /admin/analytics with its list toolbar (App\Livewire\Admin\Analytics\EngagingPages). --}}
@use('App\Support\Duration')
<div>
    <div class="analytics-panel__head">
        <h2 id="pages-heading" class="analytics-panel__title">{{ __('Most engaging pages') }}</h2>
        @if ($pages !== [])
            <x-button variant="quiet" size="sm" :href="$exportUrl" data-export="pages"><x-icon name="download" class="size-4" />CSV<span class="sr-only"> {{ __('of pages') }}</span></x-button>
        @endif
    </div>
    <p class="mt-2 max-w-2xl text-sm font-normal text-ink-muted">{{ __('Ranked by the time visitors spent reading: the tab in view and the visitor scrolling or interacting within the last :seconds seconds.', ['seconds' => app(\App\Services\LocaleService::class)->digits(\App\Constants\Analytics::IDLE_SECONDS)]) }}</p>

    <x-admin.list-toolbar :toolbar="$toolbar" :items="__('pages')" :placeholder="__('Search titles and addresses')" />

    <x-admin.list-results :density="$toolbar['density']">
        @if ($pages === [])
            @if ($toolbar['filtered'] && ! $sectionChosen)
                <x-admin.list-empty :title="__('Most engaging pages')" text="" :filtered="true" />
            @else
                <p class="text-sm font-normal text-ink-muted" data-pages-empty>{{ $sectionChosen ? __('No pages in this section were read in this period.') : __('No pages were read in this period.') }}</p>
            @endif
        @else
            <div class="relative overflow-x-auto rounded-card border border-rule">
                <table class="admin-table analytics-table w-full text-start text-sm" data-pages-table>
                    <caption class="sr-only">{{ __('Pages ranked by total engaged time') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Page') }}</th>
                            <th scope="col" class="text-end">{{ __('Engaged time') }}</th>
                            <th scope="col" class="text-end">{{ __('Per view') }}</th>
                            <th scope="col" class="text-end">{{ __('Views') }}</th>
                            <th scope="col" class="text-end">{{ __('Scroll depth') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr data-page="{{ $page['path'] }}">
                                <th scope="row">
                                    <a href="{{ $page['url'] }}" class="font-medium text-ink">{{ $page['label'] }}</a>
                                    <span class="mt-1 block text-sm font-normal text-ink-muted">{{ $page['section_label'] }} · <span class="analytics-table__path font-mono">{{ $page['path'] }}</span></span>
                                </th>
                                <td class="text-end"><time datetime="{{ Duration::iso($page['engaged_seconds']) }}">{{ Duration::short($page['engaged_seconds']) }}</time></td>
                                <td class="text-end"><time datetime="{{ Duration::iso($page['average_engaged']) }}">{{ Duration::short($page['average_engaged']) }}</time></td>
                                <td class="text-end">{{ number_format($page['views']) }}</td>
                                <td class="text-end">{{ number_format($page['average_scroll'], 0) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.list-results>
</div>
