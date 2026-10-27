{{--
    A small ranked list of the analytics report (countries, referrers, devices, browsers): label
    (with a flag for countries), visitors, share of the list and a hairline share bar. `rows` come
    from AnalyticsReportService; `export` is the URL of the full list as CSV; `empty` the sentence
    shown when the list has no rows. The list itself is the accessible form of the bars.
--}}
@props(['title', 'rows', 'export' => null, 'empty' => null, 'key' => null])
<section {{ $attributes->class(['analytics-panel']) }} @if ($key) data-ranked="{{ $key }}" @endif aria-labelledby="ranked-{{ $key }}-heading">
    <div class="analytics-panel__head">
        <h2 id="ranked-{{ $key }}-heading" class="analytics-panel__title">{{ $title }}</h2>
        @if ($export && $rows !== [])
            <x-button variant="quiet" size="sm" :href="$export" data-export="{{ $key }}"><x-icon name="download" class="size-4" />CSV<span class="sr-only"> {{ __('of :list', ['list' => mb_strtolower($title)]) }}</span></x-button>
        @endif
    </div>
    @if ($rows === [])
        <p class="mt-4 text-sm font-normal text-ink-muted">{{ $empty ?? __('Nothing was recorded in this period.') }}</p>
    @else
        <ol class="ranked-list">
            @foreach ($rows as $row)
                <li class="ranked-list__row">
                    <span class="ranked-list__label">
                        @if (($row['flag'] ?? '') !== '')
                            <span class="ranked-list__flag" aria-hidden="true">{{ $row['flag'] }}</span>
                        @endif
                        {{ $row['label'] }}
                    </span>
                    <span class="ranked-list__figure">{{ number_format($row['visitors']) }}<span class="ranked-list__share">{{ number_format($row['share'], 0) }}%</span></span>
                    <span class="share-bar" style="--share: {{ number_format(min(100, $row['share']), 1, '.', '') }}%" aria-hidden="true"><span class="share-bar__fill"></span></span>
                </li>
            @endforeach
        </ol>
    @endif
</section>
