{{-- Livewire checkbox with a label, an optional hint and an inline validation message. `name` is the bound property (e.g. form.is_featured). --}}
@props(['name', 'label', 'hint' => null])
@php
    $id = str_replace(['.', '_'], '-', $name);
    $hintId = $id.'-hint';
    $errorId = $id.'-error';
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="flex min-h-11 items-center gap-3 text-body text-ink">
        <input id="{{ $id }}" type="checkbox" wire:model="{{ $name }}" class="size-4 shrink-0 rounded-sm border-rule text-ink"
               aria-describedby="{{ trim(($hint ? $hintId : '').' '.($errors->has($name) ? $errorId : '')) }}"
               @error($name) aria-invalid="true" @enderror>
        <span>{{ $label }}</span>
    </label>
    @if ($hint)
        <p id="{{ $hintId }}" class="mt-1 pl-7 text-sm font-light text-ink-muted">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $errorId }}" class="mt-2 flex items-center gap-2 text-sm text-danger-tone" role="alert"><x-icon name="warning" class="size-4 shrink-0" /><span>{{ $message }}</span></p>
    @enderror
</div>
