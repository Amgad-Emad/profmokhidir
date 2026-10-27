{{--
    Opens a section that begins a "chapter": a Roman numeral in small caps (from `number`)
    beside an optional label, a brass hairline that draws left to right, then the heading.
    An `epigraph` slot (use <x-epigraph>) follows the heading, then an optional intro in the
    default slot. `tone`: "evergreen" on ivory (default) or "ivory" on evergreen surfaces.
--}}
@props(['number' => null, 'label' => null, 'title', 'tone' => 'evergreen', 'level' => 2, 'id' => null])
@inject('typesetter', 'App\Services\TypesetterService')
@php
    $onDark = $tone === 'ivory';
    $level = in_array((int) $level, [1, 2, 3, 4], true) ? (int) $level : 2;
    $tag = 'h'.$level;
    $numeral = $number !== null && (int) $number > 0 ? $typesetter->roman((int) $number) : null;
@endphp
<div {{ $attributes->class(['chapter-opening']) }}>
    @if ($numeral || $label)
        <p class="chapter-opening__label text-accent-text" data-rise>
            @if ($numeral)
                <span class="t-numeral"><span class="sr-only">{{ __('Chapter') }} </span>{{ $numeral }}</span>
            @endif
            @if ($label)
                <span class="t-label">{{ $label }}</span>
            @endif
        </p>
    @endif
    <hr class="rule-brass chapter-opening__rule" data-rule>
    <{{ $tag }}{!! $id ? ' id="'.e($id).'"' : '' !!} class="t-heading chapter-opening__title" data-rise>@typeset($title)</{{ $tag }}>
    @isset($epigraph)
        <div class="chapter-opening__epigraph" data-rise>{{ $epigraph }}</div>
    @endisset
    @if ($slot->isNotEmpty())
        <div class="t-body mt-6 max-w-2xl {{ $onDark ? 'text-ink' : 'text-ink-muted' }}" data-rise>{{ $slot }}</div>
    @endif
</div>
