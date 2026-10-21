{{--
    One tribute as a page of the book of remembrance: the words in Cormorant italic with
    generous leading, the name in small caps, relationship and place as a label and,
    for approved tributes with a card, a quiet link to save the share card. `heading`
    is the heading level of the (visually hidden) author heading; `cardUrl` is optional.
--}}
@props(['tribute', 'cardUrl' => null, 'heading' => 3])
@php
    $level = in_array((int) $heading, [2, 3, 4], true) ? (int) $heading : 3;
    $tag = 'h'.$level;
    $paragraphs = preg_split('/\R{2,}/u', trim((string) $tribute->message)) ?: [];
@endphp
<article {{ $attributes->class(['tribute-entry']) }}>
    <blockquote class="tribute-entry__text">
        @foreach ($paragraphs as $index => $paragraph)
            <p>@if ($index === 0)<span class="hang-quote">&ldquo;</span>@endif@typeset($paragraph)@if ($loop->last)&rdquo;@endif</p>
        @endforeach
    </blockquote>
    <footer>
        <{{ $tag }} class="tribute-entry__author smallcaps">@typeset($tribute->name)</{{ $tag }}>
        <x-meta class="tribute-entry__meta" :items="[$tribute->relationship?->label(), $tribute->location]" />
        @if ($cardUrl)
            <a href="{{ $cardUrl }}" class="tribute-entry__card t-label" download="{{ \App\Constants\TributeCards::filename((int) $tribute->getKey()) }}">Share card<span class="sr-only"> for the tribute from {{ $tribute->name }} (PNG image)</span></a>
        @endif
    </footer>
</article>
