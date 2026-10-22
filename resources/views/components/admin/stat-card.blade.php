{{--
    Dashboard figure: label, big display number, optional link. `noun` is pluralised to the count
    ("3 tributes"); `caption` is shown as written ("published"). Figures sit at the foot of the card so
    they share a baseline across a row whatever the label's length.
--}}
@props(['label', 'count', 'href' => null, 'tone' => null, 'noun' => null, 'caption' => null])
@php
    $tone = $tone instanceof \App\Constants\StatusTone ? $tone : ($tone ? \App\Constants\StatusTone::from($tone) : null);
    $tag = $href ? 'a' : 'div';
    $caption ??= $noun ? \Illuminate\Support\Str::plural($noun, $count) : null;
@endphp
<{{ $tag }}{!! $href ? ' href="'.e($href).'"' : '' !!} {{ $attributes->class(['stat-card flex flex-col gap-4 rounded-card border border-rule bg-surface-panel p-6 no-underline hover:no-underline', 'hover:border-focus focus-visible:border-focus' => $href]) }}>
    <span class="t-label text-accent-text">{{ $label }}</span>
    <span class="mt-auto flex items-end justify-between gap-3">
        <span class="figures-lining font-display text-5xl font-light leading-none text-ink" data-count>{{ number_format($count) }}</span>
        @if ($tone)
            <x-icon :name="$tone->icon()" class="mb-1 size-6 {{ $tone->textClass() }}" :label="$tone === \App\Constants\StatusTone::Bronze ? 'Needs attention' : 'Clear'" />
        @endif
    </span>
    @if ($caption)
        <span class="text-sm font-light text-ink-muted">{{ $caption }}</span>
    @endif
</{{ $tag }}>
