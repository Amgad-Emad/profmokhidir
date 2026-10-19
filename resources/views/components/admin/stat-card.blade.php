{{-- Dashboard figure: label, big display number, optional link. --}}
@props(['label', 'count', 'href' => null, 'tone' => null, 'noun' => null])
@php
    $tone = $tone instanceof \App\Constants\StatusTone ? $tone : ($tone ? \App\Constants\StatusTone::from($tone) : null);
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }}{!! $href ? ' href="'.e($href).'"' : '' !!} {{ $attributes->class(['stat-card flex flex-col gap-3 rounded-card border border-stone bg-ivory p-5 no-underline hover:no-underline', 'hover:border-bronze focus-visible:border-bronze' => $href]) }}>
    <span class="t-label text-bronze">{{ $label }}</span>
    <span class="flex items-end justify-between gap-3">
        <span class="font-display text-5xl font-light leading-none text-evergreen" data-count>{{ number_format($count) }}</span>
        @if ($tone)
            <x-icon :name="$tone->icon()" class="mb-1 size-6 {{ $tone->textClass() }}" :label="$tone === \App\Constants\StatusTone::Bronze ? 'Needs attention' : 'Clear'" />
        @endif
    </span>
    @if ($noun)
        <span class="text-sm font-light text-ash">{{ \Illuminate\Support\Str::plural($noun, $count) }}</span>
    @endif
</{{ $tag }}>
