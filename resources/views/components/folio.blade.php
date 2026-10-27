{{--
    A quiet running head (or foot, `position="bottom"`) for long pages, echoing a printed
    book: the Professor's name and the section name in small caps either side of a thin
    stone rule. Decorative repetition, so hidden from assistive technology.
--}}
@props(['section', 'position' => 'top', 'tone' => 'evergreen'])
@php
    $title = __(\App\Constants\BrandMark::TITLE);
    $bottom = $position === 'bottom';
@endphp
<div {{ $attributes->class(['folio', 'folio--'.($bottom ? 'bottom' : 'top'), 'folio--ivory' => $tone === 'ivory']) }} aria-hidden="true">
    <span @class(['smallcaps', 'hidden sm:inline' => ! $bottom])>{{ $bottom ? $section : $title }}</span>
    <span class="folio__rule"></span>
    <span @class(['smallcaps', 'hidden sm:inline' => $bottom])>{{ $bottom ? $title : $section }}</span>
</div>
