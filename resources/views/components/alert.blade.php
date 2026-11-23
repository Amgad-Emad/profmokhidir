{{--
    Inline notice on a light surface. `tone` is a StatusTone value (bronze = needs action,
    cypress = done, ash = neutral); each tone carries its own icon, so meaning never rests
    on colour alone. `role` defaults to "status"; pass "alert" for errors.
--}}
@props(['tone' => \App\Constants\StatusTone::Ash, 'role' => 'status', 'title' => null])
@php
    $tone = $tone instanceof \App\Constants\StatusTone ? $tone : \App\Constants\StatusTone::from($tone);
@endphp
<div {{ $attributes->class(['alert flex items-start gap-3 rounded-card border bg-surface-panel px-4 py-3 text-sm', $tone->badgeClasses()]) }} role="{{ $role }}">
    {{-- The icon sits in a box one text line tall, so it centres on the first line in either language. --}}
    <span class="alert__icon flex shrink-0 items-center" aria-hidden="true"><x-icon :name="$tone->icon()" class="size-5" /></span>
    <div class="min-w-0 flex-1 text-ink">
        @if ($title)
            <p class="font-medium">{{ $title }}</p>
        @endif
        <div class="font-normal">{{ $slot }}</div>
    </div>
</div>
