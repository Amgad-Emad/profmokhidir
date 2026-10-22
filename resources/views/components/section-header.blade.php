{{--
    Opens a content section: small label, brass rule that draws in, heading in .t-heading,
    and an optional intro in the slot. `tone` is the ink: "evergreen" on ivory surfaces
    (label in bronze, default) or "ivory" on evergreen surfaces (label in brass).
--}}
@props(['label' => null, 'title', 'tone' => 'evergreen', 'level' => 2, 'id' => null])
@php
    $onDark = $tone === 'ivory';
    $level = in_array((int) $level, [1, 2, 3, 4], true) ? (int) $level : 2;
    $tag = 'h'.$level;
@endphp
<div {{ $attributes->class(['section-header']) }}>
    @if ($label)
        <p class="t-label text-accent-text" data-rise>{{ $label }}</p>
    @endif
    <hr class="rule-brass mt-4" data-rule>
    <{{ $tag }}{!! $id ? ' id="'.e($id).'"' : '' !!} class="t-heading mt-8" data-rise>{{ $title }}</{{ $tag }}>
    @if ($slot->isNotEmpty())
        <div class="t-body mt-6 max-w-2xl {{ $onDark ? 'text-ink' : 'text-ink-muted' }}" data-rise>{{ $slot }}</div>
    @endif
</div>
