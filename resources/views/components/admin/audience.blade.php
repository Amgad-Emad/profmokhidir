{{--
    The dashboard's Audience section (users with "view analytics"): the last 30 days' headline
    figures against the 30 before, the three most engaging pages and the three leading countries,
    and the way to the full report. While analytics is off it says so, and how to switch it on.
    `audience` comes from AnalyticsReportService::audience().
--}}
@props(['audience'])
@use('App\Constants\Analytics')
@use('App\Constants\AdminNavigation')
<section {{ $attributes->class(['mt-14']) }} aria-labelledby="audience-heading" data-section="audience">
    <div class="dashboard-heading">
        <h2 id="audience-heading" class="dashboard-heading__title">Audience</h2>
        <hr class="rule-brass flex-1" data-rule>
    </div>

    @unless ($audience['enabled'])
        <div class="analytics-off mt-6" data-analytics-off>
            <x-icon name="info" class="mt-0.5 size-5 text-accent-text" />
            <p class="text-base font-normal text-ink-muted">
                Visitor analytics is switched off, so nothing is being recorded. It is first-party and cookieless, and is switched on in
                @can(\App\Constants\Permissions::MANAGE_SETTINGS)
                    <a href="{{ route(AdminNavigation::routeFor(AdminNavigation::SITE_SETTINGS)) }}#analytics">Site settings</a>
                @else
                    Site settings by an administrator
                @endcan
                once the client has approved it.
            </p>
        </div>
    @endunless

    @if ($audience['hasData'])
        <p class="mt-6 text-sm font-normal text-ink-muted">Last 30 days, {{ $audience['period']['label'] }}, against {{ $audience['period']['comparison'] }}.</p>
        <div class="analytics-figures mt-4">
            @foreach ($audience['headline'] as $key => $figure)
                <x-admin.analytics-figure :figure="$figure" :comparison="$audience['period']['comparison']" :key="$key" />
            @endforeach
        </div>

        <div class="analytics-grid analytics-grid--two mt-6">
            <section class="analytics-panel" aria-labelledby="audience-pages-heading" data-audience-pages>
                <h3 id="audience-pages-heading" class="analytics-panel__title">Most engaging pages</h3>
                <ol class="ranked-list">
                    @foreach ($audience['pages'] as $page)
                        <li class="ranked-list__row">
                            <span class="ranked-list__label"><a href="{{ $page['url'] }}">{{ $page['label'] }}</a></span>
                            <span class="ranked-list__figure"><time datetime="{{ \App\Support\Duration::iso($page['engaged_seconds']) }}">{{ \App\Support\Duration::short($page['engaged_seconds']) }}</time><span class="ranked-list__share">{{ number_format($page['views']) }} {{ \Illuminate\Support\Str::plural('view', $page['views']) }}</span></span>
                        </li>
                    @endforeach
                </ol>
            </section>
            <x-admin.ranked-list title="Countries" :rows="$audience['countries']" key="audience-countries" />
        </div>
    @elseif ($audience['enabled'])
        <x-empty-state message="No visits have been recorded in the last 30 days yet." class="py-8" />
    @endif

    <p class="mt-6"><x-button variant="secondary" size="sm" :href="route(Analytics::ADMIN_ROUTE)" data-open-analytics>Open the full report<x-icon name="arrow" class="size-4" /></x-button></p>
</section>
