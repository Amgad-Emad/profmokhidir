{{--
    Inline notice on a light surface. `tone` is a StatusTone value (bronze = needs action,
    cypress = done, ash = neutral); each tone carries its own icon, so meaning never rests
    on colour alone. `role` defaults to "status"; pass "alert" for errors.
--}}
@props(['tone' => \App\Constants\StatusTone::Ash, 'role' => 'status', 'title' => null])
@php
    $tone = $tone instanceof \App\Constants\StatusTone ? $tone : \App\Constants\StatusTone::from($tone);
@endphp
<div {{ $attributes->class(['flex items-start gap-3 rounded-card border bg-surface-panel px-4 py-3 text-sm', $tone->badgeClasses()]) }} role="{{ $role }}">
    <x-icon :name="$tone->icon()" class="mt-0.5 size-5" />
    <div class="text-ink">
        @if ($title)
            <p class="font-medium">{{ $title }}</p>
        @endif
        <div class="font-light">{{ $slot }}</div>
    </div>
</div>
