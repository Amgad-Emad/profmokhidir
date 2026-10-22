@use('App\Constants\AuditAction')
<x-layouts.admin title="Dashboard">
    <header class="flex flex-col gap-2">
        <p class="t-label text-bronze">Dashboard</p>
        <h1 class="t-heading">{{ $greeting['salutation'] }}, {{ auth()->user()->name }}</h1>
        <p class="smallcaps text-xl text-ash" data-greeting-date><time datetime="{{ $greeting['datetime'] }}">{{ $greeting['date'] }}</time></p>
        <p class="mt-2 max-w-2xl text-base font-light text-ash">Signed in as {{ auth()->user()->primaryRoleLabel() }}. Here is what is published and what is waiting for review.</p>
    </header>

    {{-- Needs attention --}}
    <section class="mt-12" aria-labelledby="attention-heading" data-section="attention">
        <div class="flex items-center justify-between gap-4">
            <h2 id="attention-heading" class="font-display text-3xl font-light">Needs attention</h2>
            <hr class="rule-brass flex-1" data-rule>
        </div>

        @if ($attention === [])
            <x-empty-state message="Nothing is waiting for review." class="py-8" />
        @else
            <ul class="mt-6 divide-y divide-stone rounded-card border border-stone bg-ivory">
                @foreach ($attention as $key => $queue)
                    <li>
                        <a href="{{ route($queue['route']) }}" class="flex items-center justify-between gap-4 px-5 py-4 no-underline hover:bg-linen hover:no-underline" data-queue="{{ $key }}">
                            <span class="flex items-center gap-3">
                                <x-icon :name="$queue['tone']->icon()" class="size-5 {{ $queue['tone']->textClass() }}" />
                                <span class="font-medium">{{ $queue['label'] }}</span>
                            </span>
                            <span class="flex items-center gap-3 text-sm text-ash">
                                <span>{{ $queue['count'] }} {{ \Illuminate\Support\Str::plural($queue['noun'], $queue['count']) }}</span>
                                <x-icon name="arrow" class="size-4 text-bronze" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Queues --}}
    <section class="mt-12" aria-labelledby="queues-heading" data-section="queues">
        <div class="flex items-center justify-between gap-4">
            <h2 id="queues-heading" class="font-display text-3xl font-light">Review queues</h2>
            <hr class="rule-brass flex-1" data-rule>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($queues as $key => $queue)
                <x-admin.stat-card :label="$queue['label']" :count="$queue['count']" :tone="$queue['tone']" :noun="$queue['noun']"
                                   :href="auth()->user()->can($queue['permission']) ? route($queue['route']) : null" data-stat="{{ $key }}" />
            @endforeach
        </div>
    </section>

    {{-- Published per section --}}
    <section class="mt-12" aria-labelledby="published-heading" data-section="published">
        <div class="flex items-center justify-between gap-4">
            <h2 id="published-heading" class="font-display text-3xl font-light">Published</h2>
            <hr class="rule-brass flex-1" data-rule>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($published as $key => $card)
                <x-admin.stat-card :label="$card['label']" :count="$card['count']" caption="published"
                                   :href="auth()->user()->can($card['permission']) ? route($card['route']) : null" data-stat="{{ $key }}" />
            @endforeach
        </div>
    </section>

    {{-- Recent activity --}}
    <section class="mt-12" aria-labelledby="activity-heading" data-section="activity">
        <div class="flex items-center justify-between gap-4">
            <h2 id="activity-heading" class="font-display text-3xl font-light">Recent activity</h2>
            <hr class="rule-brass flex-1" data-rule>
        </div>

        @if ($activity->isEmpty())
            <x-empty-state message="No activity has been recorded yet." class="py-8" />
        @else
            <ol class="mt-6 divide-y divide-stone rounded-card border border-stone bg-ivory" data-activity>
                @foreach ($activity as $log)
                    <li class="flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-baseline sm:justify-between sm:gap-6">
                        <p class="text-base">
                            <span class="font-medium">{{ $log->user?->name ?? 'Visitor' }}</span>
                            <span class="font-light">{{ AuditAction::label($log->action) }}</span>
                            @if (is_array($log->meta) && isset($log->meta['label']))
                                <span class="font-light text-ash">· {{ $log->meta['label'] }}</span>
                            @endif
                        </p>
                        <time datetime="{{ $log->created_at->toIso8601String() }}" class="shrink-0 text-sm font-light text-ash" title="{{ $log->created_at->format(\App\Constants\DateFormats::LONG.', H:i') }}">{{ $log->created_at->diffForHumans() }}</time>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</x-layouts.admin>
