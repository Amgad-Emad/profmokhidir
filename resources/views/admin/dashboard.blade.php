{{--
    The dashboard home, task first: what needs attention now (one card per queue, with the action),
    the quick actions, the "Before launch" checklist, recent activity in plain language, and the
    published count per section. Data from DashboardService::summary().
--}}
<x-layouts.admin title="Dashboard">
    <header class="flex flex-col gap-2">
        <p class="t-label text-accent-text">Dashboard</p>
        <h1 class="t-heading">{{ $greeting['salutation'] }}, {{ auth()->user()->name }}</h1>
        <p class="smallcaps text-xl text-ink-muted" data-greeting-date><time datetime="{{ $greeting['datetime'] }}">{{ $greeting['date'] }}</time></p>
    </header>

    {{-- Needs your attention --}}
    <section class="mt-12" aria-labelledby="attention-heading" data-section="attention">
        <div class="dashboard-heading">
            <h2 id="attention-heading" class="dashboard-heading__title">Needs your attention</h2>
            <hr class="rule-brass flex-1" data-rule>
        </div>

        @if ($cards === [])
            <p class="dashboard-calm" data-attention-empty><x-icon name="check" class="size-5" />Nothing is waiting for review. Everything is up to date.</p>
        @else
            <div class="attention-grid mt-6">
                @foreach ($cards as $card)
                    <x-admin.attention-card :card="$card" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Quick actions --}}
    @if ($actions !== [])
        <section class="mt-14" aria-labelledby="actions-heading" data-section="actions">
            <div class="dashboard-heading">
                <h2 id="actions-heading" class="dashboard-heading__title">Start something</h2>
                <hr class="rule-brass flex-1" data-rule>
            </div>
            <ul class="quick-actions mt-6">
                @foreach ($actions as $action)
                    <li>
                        <a href="{{ $action['url'] }}" class="quick-action" data-quick-action="{{ $action['key'] }}">
                            <x-icon :name="$action['icon']" class="quick-action__icon size-6" />
                            <span class="quick-action__label">{{ $action['label'] }}</span>
                            <span class="quick-action__hint">{{ $action['hint'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="dashboard-columns mt-14">
        {{-- Before launch --}}
        @if ($checklist !== null)
            <section aria-labelledby="launch-heading" data-section="launch">
                <div class="dashboard-heading">
                    <h2 id="launch-heading" class="dashboard-heading__title">Before launch</h2>
                    <hr class="rule-brass flex-1" data-rule>
                </div>
                <p class="mt-3 text-base font-light text-ink-muted">What still needs doing before the site is announced. Each line updates as the work is done.</p>
                <x-admin.launch-checklist :checklist="$checklist" class="mt-6" />
            </section>
        @endif

        {{-- Recent activity --}}
        <section aria-labelledby="activity-heading" data-section="activity">
            <div class="dashboard-heading">
                <h2 id="activity-heading" class="dashboard-heading__title">Recent activity</h2>
                <hr class="rule-brass flex-1" data-rule>
            </div>

            @if ($feed === [])
                <x-empty-state message="No activity has been recorded yet." class="py-8" />
            @else
                <ol class="activity-feed mt-6" data-activity>
                    @foreach ($feed as $entry)
                        <li class="activity-feed__item">
                            <p>{{ $entry['sentence'] }}</p>
                            <time datetime="{{ $entry['at']->toIso8601String() }}" title="{{ $entry['at']->format(\App\Constants\DateFormats::LONG.', H:i') }}">{{ $entry['at']->diffForHumans() }}</time>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>

    {{-- Published per section --}}
    <section class="mt-14" aria-labelledby="published-heading" data-section="published">
        <div class="dashboard-heading">
            <h2 id="published-heading" class="dashboard-heading__title">On the site now</h2>
            <hr class="rule-brass flex-1" data-rule>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($published as $key => $card)
                <x-admin.stat-card :label="$card['label']" :count="$card['count']" caption="published"
                                   :href="auth()->user()->can($card['permission']) ? route($card['route']) : null" data-stat="{{ $key }}" />
            @endforeach
        </div>
    </section>
</x-layouts.admin>
