{{-- Draft/published: a segmented <x-form.radio-group> (each choice carries its icon, so the state is readable without colour) and the public link once published. --}}
@props(['name' => 'form.status', 'options' => \App\Constants\ContentStatus::labels(), 'publicUrl' => null])
@php
    $icons = collect($options)->mapWithKeys(fn ($label, $value) => [$value => \App\Constants\StatusTone::forContent(\App\Constants\ContentStatus::from($value))->icon()])->all();
@endphp
<x-form.radio-group :model="$name" label="Status" :options="$options" :icons="$icons" variant="segmented" required :class="$attributes->get('class')" data-status-field>
    @if ($publicUrl)
        <p class="mt-3 text-sm"><a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="link-meta" data-view-on-site>View on site<span class="sr-only"> (opens in a new tab)</span></a></p>
    @endif
</x-form.radio-group>
