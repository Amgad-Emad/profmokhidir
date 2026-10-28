{{--
    /admin/analytics: the audience report for a date range against the period before it. Headline
    figures, visitors over time (chart + table), countries (list + map), the most engaging pages,
    referrers, devices and browsers, each with a CSV. Data from AnalyticsReportService::report();
    the chart script (resources/js/admin/analytics-chart.js) is loaded on this page only.
--}}
@use('App\Constants\Analytics')
@use('App\Constants\AnalyticsRange')
@use('App\Constants\AdminNavigation')
@use('App\Support\Duration')
@php
    $exportUrl = fn (string $export): string => route(Analytics::EXPORT_ROUTE, ['export' => $export, ...$query]);
    $isCustom = $period['range'] === AnalyticsRange::Custom;
@endphp
<x-layouts.admin :title="__('Analytics')">
    <x-admin.page-header :eyebrow="__('Audience')" :title="__('Analytics')"
        :intro="__('Who reads the site and what holds their attention. First-party and cookieless: no personal data is kept, and visitors are counted once per day.')" />

    @unless ($enabled)
        <div class="analytics-off mt-8" data-analytics-off>
            <x-icon name="info" class="mt-0.5 size-5 text-accent-text" />
            <p class="text-base font-normal text-ink-muted">
                {{ __('Analytics is switched off, so nothing new is being recorded. Figures already collected stay readable here.') }}
                @can(\App\Constants\Permissions::MANAGE_SETTINGS)
                    {!! __('Switch it on in :settings once the client has approved it.', ['settings' => '<a href="'.e(route(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS))).'#analytics">'.e(__('Site settings')).'</a>']) !!}
                @else
                    {{ __('An administrator can switch it on in Site settings once the client has approved it.') }}
                @endcan
            </p>
        </div>
    @endunless

    {{-- Date range --}}
    <form method="GET" action="{{ route(Analytics::ADMIN_ROUTE) }}" class="analytics-range mt-10" novalidate data-range-form
          x-data="{ range: @js($period['range']->value) }">
        <x-form.select name="range" :label="__('Period')" :optional="false" :options="AnalyticsRange::labels()" :value="$period['range']->value" x-model="range" data-range />
        <div x-show="range === 'custom'" data-range-from><x-form.date name="from" :label="__('First day')" :optional="false" :value="$isCustom ? $period['from']->toDateString() : null" :max="now()->toDateString()" /></div>
        <div x-show="range === 'custom'" data-range-to><x-form.date name="to" :label="__('Last day')" :optional="false" :value="$isCustom ? $period['to']->toDateString() : null" :max="now()->toDateString()" /></div>
        @if ($section)
            <input type="hidden" name="section" value="{{ $section }}">
        @endif
        <div class="analytics-range__apply"><x-button type="submit" variant="primary" size="sm" data-leave-ok>{{ __('Show') }}</x-button></div>
    </form>
    <p class="mt-4 text-sm font-normal text-ink-muted" data-period>{{ __(':period, compared with :comparison.', ['period' => $period['label'], 'comparison' => $period['comparison']]) }}</p>

    {{-- Headline figures --}}
    <section class="mt-8" aria-labelledby="headline-heading">
        <h2 id="headline-heading" class="sr-only">{{ __('Headline figures') }}</h2>
        <div class="analytics-figures">
            @foreach ($headline as $key => $figure)
                <x-admin.analytics-figure :figure="$figure" :comparison="$period['comparison']" :key="$key" />
            @endforeach
        </div>
    </section>

    @unless ($hasData)
        <x-empty-state :message="__('No visits were recorded in this period.')" class="py-10" />
    @endunless

    {{-- Visitors over time --}}
    <section class="analytics-panel mt-8" aria-labelledby="visitors-heading" data-visitors>
        <div class="analytics-panel__head">
            <h2 id="visitors-heading" class="analytics-panel__title">{{ __('Visitors over time') }}</h2>
            <x-button variant="quiet" size="sm" :href="$exportUrl(Analytics::EXPORT_VISITORS)" data-export="visitors"><x-icon name="download" class="size-4" />CSV<span class="sr-only"> {{ __('of visitors per day') }}</span></x-button>
        </div>
        <div class="analytics-legend mt-3" aria-hidden="true">
            <span class="analytics-legend__item"><span class="analytics-legend__swatch"></span>{{ __('This period') }}</span>
            <span class="analytics-legend__item"><span class="analytics-legend__swatch analytics-legend__swatch--compare"></span>{{ ucfirst($period['comparison']) }}</span>
        </div>
        <div class="analytics-chart" data-visitors-chart data-series="{{ json_encode($series) }}" data-current-name="{{ __('This period') }}" data-previous-name="{{ ucfirst($period['comparison']) }}">
            @foreach (['line', 'compare', 'grid', 'label', 'panel'] as $ink)
                <span class="analytics-chart__ink" data-ink="{{ $ink }}"></span>
            @endforeach
            <canvas role="img" aria-label="{{ __('Line chart of unique visitors, :period, with :comparison as a dashed line. The table below gives the figures.', ['period' => $period['label'], 'comparison' => $period['comparison']]) }}"></canvas>
        </div>
        <details class="mt-6" data-chart-table>
            <summary class="cursor-pointer text-sm font-medium text-ink">{{ __('Show the figures as a table') }}</summary>
            <div class="relative mt-4 max-h-96 overflow-auto rounded-card border border-rule" tabindex="0" role="region" aria-label="{{ __('Visitors over time, as a table') }}">
                <table class="admin-table analytics-table w-full text-start text-sm">
                    <caption class="sr-only">{{ $period['days'] > Analytics::DAILY_CHART_MAX_DAYS ? __('Unique visitors per month') : __('Unique visitors per day') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">{{ $period['days'] > Analytics::DAILY_CHART_MAX_DAYS ? __('Month') : __('Day') }}</th>
                            <th scope="col" class="text-end">{{ __('Visitors') }}</th>
                            <th scope="col" class="text-end">{{ ucfirst($period['comparison']) }}</th>
                            <th scope="col" class="text-end">{{ __('Page views') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($series as $point)
                            <tr>
                                <th scope="row">{{ $point['label'] }}</th>
                                <td class="text-end">{{ number_format($point['visitors']) }}</td>
                                <td class="text-end">{{ number_format($point['previous']) }}</td>
                                <td class="text-end">{{ number_format($point['pageviews']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </section>

    {{-- Countries --}}
    <div class="analytics-grid analytics-grid--map mt-6">
        <x-admin.ranked-list :title="__('Countries')" :rows="$countries" :export="$exportUrl(Analytics::EXPORT_COUNTRIES)" key="countries"
            :empty="__('No countries were recorded in this period.')" />
        <section class="analytics-panel" aria-labelledby="map-heading">
            <div class="analytics-panel__head">
                <h2 id="map-heading" class="analytics-panel__title">{{ __('Where visitors are') }}</h2>
                <span class="text-sm font-normal text-ink-muted">{{ trans_choice('{0} No countries|{1} :count country|[2,*] :count countries', $countryCount, ['count' => number_format($countryCount)]) }}</span>
            </div>
            <x-admin.world-map :countries="$map" class="mt-4" />
        </section>
    </div>

    {{-- Most engaging pages (its own list toolbar: search, sections, sort) --}}
    <section class="analytics-panel mt-6" aria-labelledby="pages-heading" data-pages>
        <livewire:admin.analytics.engaging-pages :from="$period['from']->toDateString()" :to="$period['to']->toDateString()" :export-query="$query" :section="$section" />
    </section>

    {{-- Where they came from, and how --}}
    <div class="analytics-grid analytics-grid--three mt-6">
        <x-admin.ranked-list :title="__('Referrers')" :rows="$referrers" :export="$exportUrl(Analytics::EXPORT_REFERRERS)" key="referrers" />
        <x-admin.ranked-list :title="__('Devices')" :rows="$devices" :export="$exportUrl(Analytics::EXPORT_DEVICES)" key="devices" />
        <x-admin.ranked-list :title="__('Browsers')" :rows="$browsers" :export="$exportUrl(Analytics::EXPORT_BROWSERS)" key="browsers" />
    </div>

    @if ($campaigns !== [])
        <x-admin.ranked-list :title="__('Campaign sources')" :rows="$campaigns" key="campaigns" class="mt-6" />
    @endif

    <p class="mt-10 max-w-3xl text-sm font-normal text-ink-muted" data-retention>
        {{ __('Page views are kept for :days days, then only the daily totals remain, for :months months.', ['days' => app(\App\Services\LocaleService::class)->digits(Analytics::RAW_RETENTION_DAYS), 'months' => app(\App\Services\LocaleService::class)->digits(Analytics::ROLLUP_RETENTION_MONTHS)]) }}
        {{ __('Countries are told from the address, which is then discarded; no cookie is set and nothing identifies a person.') }}
    </p>

    @vite('resources/js/admin/analytics-chart.js')
</x-layouts.admin>
