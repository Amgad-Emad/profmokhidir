{{--
    One headline figure of the analytics report: label, value, and the change against the
    comparison period in words and an arrow (never colour alone). `figure` comes from
    AnalyticsReportService::headline(); `comparison` names the period ("the previous 30 days").
--}}
@props(['figure', 'comparison', 'key' => null])
@php
    $change = $figure['change'];
    $direction = match (true) {
        $change === null => 'none',
        abs($change) < 0.5 => 'flat',
        $change > 0 => 'up',
        default => 'down',
    };
@endphp
<div {{ $attributes->class(['analytics-figure']) }} @if ($key) data-figure="{{ $key }}" @endif>
    <span class="t-label text-accent-text">{{ $figure['label'] }}</span>
    <span class="analytics-figure__value" data-value>{{ $figure['display'] }}</span>
    <span class="analytics-figure__change" data-direction="{{ $direction }}">
        @if ($direction === 'none')
            <x-icon name="minus" class="size-4" />No figures for {{ $comparison }}
        @elseif ($direction === 'flat')
            <x-icon name="minus" class="size-4" />Unchanged on {{ $comparison }}
        @else
            <x-icon name="arrow" class="size-4" />{{ $direction === 'up' ? 'Up' : 'Down' }} {{ number_format(abs($change), 0) }}% on {{ $comparison }}
        @endif
    </span>
    <span class="text-sm font-light text-ink-muted">{{ $figure['hint'] }}</span>
</div>
