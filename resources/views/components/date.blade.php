{{-- The one way a date is written: <time> with a machine value and the long format. --}}
@props(['date', 'format' => \App\Constants\DateFormats::LONG])
@if ($date)
    <time {{ $attributes->merge(['datetime' => $date->format(\App\Constants\DateFormats::MACHINE)]) }}>{{ \Illuminate\Support\Carbon::instance($date)->translatedFormat($format) }}</time>
@endif
