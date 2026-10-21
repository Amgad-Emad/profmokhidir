{{--
    <x-video-label :video="$video" />
    The one label line of a recording: event, place and date, separated by the metadata dot.
    `tone` is the ink: "evergreen" on ivory surfaces (bronze, default) or "ivory" on evergreen (brass).
    Renders nothing when the recording has none of the three.
--}}
@props(['video', 'tone' => 'evergreen'])
@php $parts = array_values(array_filter([$video->event, $video->location], 'filled')); @endphp
@if ($video->date || $parts !== [])
    <p {{ $attributes->class(['t-label', $tone === 'ivory' ? 'text-brass' : 'text-bronze']) }}>
        @foreach ($parts as $index => $part)
            @if ($index > 0){{ \App\Constants\DateFormats::META_SEPARATOR }}@endif{{ $part }}
        @endforeach
        @if ($video->date)
            @if ($parts !== []){{ \App\Constants\DateFormats::META_SEPARATOR }}@endif<x-date :date="$video->date" />
        @endif
    </p>
@endif
