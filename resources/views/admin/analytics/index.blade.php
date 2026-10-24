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
<x-layouts.admin title="Analytics">
    <x-admin.page-header eyebrow="Audience" title="Analytics"
        intro="Who reads the site and what holds their attention. First-party and cookieless: no personal data is kept, and visitors are counted once per day." />

    @unless ($enabled)
        <div class="analytics-off mt-8" data-analytics-off>
            <x-icon name="info" class="mt-0.5 size-5 text-accent-text" />
            <p class="text-base font-light text-ink-muted">
                Analytics is switched off, so nothing new is being recorded. Figures already collected stay readable here.
                @can(\App\Constants\Permissions::MANAGE_SETTINGS)
                    Switch it on in <a href="{{ route(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS)) }}#analytics">Site settings</a> once the client has approved it.
                @else
                    An administrator can switch it on in Site settings once the client has approved it.
                @endcan
            </p>
        </div>
    @endunless

    {{-- Date range --}}
    <form method="GET" action="{{ route(Analytics::ADMIN_ROUTE) }}" class="analytics-range mt-10" novalidate data-range-form
          x-data="{ range: @js($period['range']->value) }">
        <x-form.select name="range" label="Period" :optional="false" :options="AnalyticsRange::labels()" :value="$period['range']->value" x-model="range" data-range />
        <div x-show="range === 'custom'" data-range-from><x-form.date name="from" label="First day" :optional="false" :value="$isCustom ? $period['from']->toDateString() : null" :max="now()->toDateString()" /></div>
        <div x-show="range === 'custom'" data-range-to><x-form.date name="to" label="Last day" :optional="false" :value="$isCustom ? $period['to']->toDateString() : null" :max="now()->toDateString()" /></div>
        @if ($section)
            <input type="hidden" name="section" value="{{ $section }}">
        @endif
        <div class="analytics-range__apply"><x-button type="submit" variant="primary" size="sm" data-leave-ok>Show</x-button></div>
    </form>
    <p class="mt-4 text-sm font-light text-ink-muted" data-period>{{ $period['label'] }}, compared with {{ $period['comparison'] }}.</p>

    {{-- Headline figures --}}
    <section class="mt-8" aria-labelledby="headline-heading">
        <h2 id="headline-heading" class="sr-only">Headline figures</h2>
        <div class="analytics-figures">
            @foreach ($headline as $key => $figure)
                <x-admin.analytics-figure :figure="$figure" :comparison="$period['comparison']" :key="$key" />
            @endforeach
        </div>
    </section>

    @unless ($hasData)
        <x-empty-state message="No visits were recorded in this period." class="py-10" />
    @endunless

    {{-- Visitors over time --}}
    <section class="analytics-panel mt-8" aria-labelledby="visitors-heading" data-visitors>
        <div class="analytics-panel__head">
            <h2 id="visitors-heading" class="analytics-panel__title">Visitors over time</h2>
            <x-button variant="quiet" size="sm" :href="$exportUrl(Analytics::EXPORT_VISITORS)" data-export="visitors"><x-icon name="download" class="size-4" />CSV<span class="sr-only"> of visitors per day</span></x-button>
        </div>
        <div class="analytics-legend mt-3" aria-hidden="true">
            <span class="analytics-legend__item"><span class="analytics-legend__swatch"></span>This period</span>
            <span class="analytics-legend__item"><span class="analytics-legend__swatch analytics-legend__swatch--compare"></span>{{ ucfirst($period['comparison']) }}</span>
        </div>
        <div class="analytics-chart" data-visitors-chart data-series="{{ json_encode($series) }}" data-current-name="This period" data-previous-name="{{ ucfirst($period['comparison']) }}">
            @foreach (['line', 'compare', 'grid', 'label', 'panel'] as $ink)
                <span class="analytics-chart__ink" data-ink="{{ $ink }}"></span>
            @endforeach
            <canvas role="img" aria-label="Line chart of unique visitors, {{ $period['label'] }}, with {{ $period['comparison'] }} as a dashed line. The table below gives the figures."></canvas>
        </div>
        <details class="mt-6" data-chart-table>
            <summary class="cursor-pointer text-sm font-medium text-ink">Show the figures as a table</summary>
            <div class="relative mt-4 max-h-96 overflow-auto rounded-card border border-rule" tabindex="0" role="region" aria-label="Visitors over time, as a table">
                <table class="admin-table analytics-table w-full text-left text-sm">
                    <caption class="sr-only">Unique visitors per {{ $period['days'] > Analytics::DAILY_CHART_MAX_DAYS ? 'month' : 'day' }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">{{ $period['days'] > Analytics::DAILY_CHART_MAX_DAYS ? 'Month' : 'Day' }}</th>
                            <th scope="col" class="text-right">Visitors</th>
                            <th scope="col" class="text-right">{{ ucfirst($period['comparison']) }}</th>
                            <th scope="col" class="text-right">Page views</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($series as $point)
                            <tr>
                                <th scope="row">{{ $point['label'] }}</th>
                                <td class="text-right">{{ number_format($point['visitors']) }}</td>
                                <td class="text-right">{{ number_format($point['previous']) }}</td>
                                <td class="text-right">{{ number_format($point['pageviews']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </section>

    {{-- Countries --}}
    <div class="analytics-grid analytics-grid--map mt-6">
        <x-admin.ranked-list title="Countries" :rows="$countries" :export="$exportUrl(Analytics::EXPORT_COUNTRIES)" key="countries"
            empty="No countries were recorded in this period." />
        <section class="analytics-panel" aria-labelledby="map-heading">
            <div class="analytics-panel__head">
                <h2 id="map-heading" class="analytics-panel__title">Where visitors are</h2>
                <span class="text-sm font-light text-ink-muted">{{ number_format($countryCount) }} {{ \Illuminate\Support\Str::plural('country', $countryCount) }}</span>
            </div>
            <x-admin.world-map :countries="$map" class="mt-4" />
        </section>
    </div>

    {{-- Most engaging pages --}}
    <section class="analytics-panel mt-6" aria-labelledby="pages-heading" data-pages>
        <div class="analytics-panel__head">
            <h2 id="pages-heading" class="analytics-panel__title">Most engaging pages</h2>
            @if ($pages !== [])
                <x-button variant="quiet" size="sm" :href="$exportUrl(Analytics::EXPORT_PAGES)" data-export="pages"><x-icon name="download" class="size-4" />CSV<span class="sr-only"> of pages</span></x-button>
            @endif
        </div>
        <p class="mt-2 max-w-2xl text-sm font-light text-ink-muted">Ranked by the time visitors spent reading: the tab in view and the visitor scrolling or interacting within the last {{ Analytics::IDLE_SECONDS }} seconds.</p>
        <form method="GET" action="{{ route(Analytics::ADMIN_ROUTE) }}" class="analytics-range mt-5" novalidate data-section-form>
            @foreach (array_intersect_key($query, array_flip(['range', 'from', 'to'])) as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <x-form.select name="section" label="Section" :optional="false" :options="['' => 'All sections', ...$sections]" :value="$section ?? ''" data-section-filter />
            <div class="analytics-range__apply"><x-button type="submit" variant="secondary" size="sm" data-leave-ok>Filter</x-button></div>
        </form>

        @if ($pages === [])
            <p class="mt-6 text-sm font-light text-ink-muted" data-pages-empty>{{ $section ? 'No pages in this section were read in this period.' : 'No pages were read in this period.' }}</p>
        @else
            <div class="relative mt-6 overflow-x-auto rounded-card border border-rule">
                <table class="admin-table analytics-table w-full text-left text-sm" data-pages-table>
                    <caption class="sr-only">Pages ranked by total engaged time</caption>
                    <thead>
                        <tr>
                            <th scope="col">Page</th>
                            <th scope="col" class="text-right">Engaged time</th>
                            <th scope="col" class="text-right">Per view</th>
                            <th scope="col" class="text-right">Views</th>
                            <th scope="col" class="text-right">Scroll depth</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr data-page="{{ $page['path'] }}">
                                <th scope="row">
                                    <a href="{{ $page['url'] }}" class="font-medium text-ink">{{ $page['label'] }}</a>
                                    <span class="mt-1 block text-xs font-light text-ink-muted">{{ $page['section_label'] }} · <span class="analytics-table__path font-mono">{{ $page['path'] }}</span></span>
                                </th>
                                <td class="text-right"><time datetime="{{ Duration::iso($page['engaged_seconds']) }}">{{ Duration::short($page['engaged_seconds']) }}</time></td>
                                <td class="text-right"><time datetime="{{ Duration::iso($page['average_engaged']) }}">{{ Duration::short($page['average_engaged']) }}</time></td>
                                <td class="text-right">{{ number_format($page['views']) }}</td>
                                <td class="text-right">{{ number_format($page['average_scroll'], 0) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Where they came from, and how --}}
    <div class="analytics-grid analytics-grid--three mt-6">
        <x-admin.ranked-list title="Referrers" :rows="$referrers" :export="$exportUrl(Analytics::EXPORT_REFERRERS)" key="referrers" />
        <x-admin.ranked-list title="Devices" :rows="$devices" :export="$exportUrl(Analytics::EXPORT_DEVICES)" key="devices" />
        <x-admin.ranked-list title="Browsers" :rows="$browsers" :export="$exportUrl(Analytics::EXPORT_BROWSERS)" key="browsers" />
    </div>

    @if ($campaigns !== [])
        <x-admin.ranked-list title="Campaign sources" :rows="$campaigns" key="campaigns" class="mt-6" />
    @endif

    <p class="mt-10 max-w-3xl text-sm font-light text-ink-muted" data-retention>
        Page views are kept for {{ Analytics::RAW_RETENTION_DAYS }} days, then only the daily totals remain, for {{ Analytics::ROLLUP_RETENTION_MONTHS }} months.
        Countries are told from the address, which is then discarded; no cookie is set and nothing identifies a person.
    </p>

    @vite('resources/js/admin/analytics-chart.js')
</x-layouts.admin>
