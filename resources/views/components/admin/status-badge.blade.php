{{-- Pill badge for a status on ivory/linen: icon + text in the tone's colour. --}}
@props(['tone', 'label'])
@php
    $tone = $tone instanceof \App\Constants\StatusTone ? $tone : \App\Constants\StatusTone::from($tone);
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium uppercase tracking-[0.2em]', $tone->badgeClasses()]) }}>
    <x-icon :name="$tone->icon()" class="size-3.5" />
    {{ $label }}
</span>
