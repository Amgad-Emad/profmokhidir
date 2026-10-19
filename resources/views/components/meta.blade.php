{{--
    The one metadata line: small bronze label, parts joined by " · ". Strings are
    escaped, dates become <time>, empty parts are dropped. `tone="ivory"` on evergreen.
--}}
@props(['items' => [], 'tone' => 'evergreen'])
@php
    $parts = [];
    foreach ($items as $item) {
        if ($item instanceof \DateTimeInterface) {
            $parts[] = '<time datetime="'.e($item->format(\App\Constants\DateFormats::MACHINE)).'">'.e($item->format(\App\Constants\DateFormats::LONG)).'</time>';
        } elseif ((is_string($item) || is_numeric($item)) && trim((string) $item) !== '') {
            $parts[] = e(trim((string) $item));
        }
    }
@endphp
@if ($parts !== [])
    <p {{ $attributes->class(['t-label', $tone === 'ivory' ? 'text-brass' : 'text-bronze']) }}>{!! implode(e(\App\Constants\DateFormats::META_SEPARATOR), $parts) !!}</p>
@endif
