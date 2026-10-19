{{-- Livewire multi-line field with an inline validation message. --}}
@props(['name', 'label', 'rows' => 5, 'hint' => null, 'required' => false, 'mono' => false])
@php
    $id = str_replace(['.', '_'], '-', $name);
    $hintId = $id.'-hint';
    $errorId = $id.'-error';
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="t-label block text-bronze">{{ $label }}@if ($required) <span aria-hidden="true">*</span>@endif</label>
    @if ($hint)
        <p id="{{ $hintId }}" class="mt-2 text-sm font-light text-ash">{{ $hint }}</p>
    @endif
    <textarea id="{{ $id }}" rows="{{ $rows }}" wire:model.blur="{{ $name }}"
              @if ($required) aria-required="true" @endif
              aria-describedby="{{ trim(($hint ? $hintId : '').' '.($errors->has($name) ? $errorId : '')) }}"
              @error($name) aria-invalid="true" @enderror
              {{ $attributes->except('class')->merge(['class' => 'mt-2 block w-full resize-y rounded-card border border-stone bg-ivory px-4 py-3 text-evergreen placeholder:text-ash focus:border-bronze '.($mono ? 'font-mono text-sm' : 'text-body')]) }}></textarea>
    @error($name)
        <p id="{{ $errorId }}" class="mt-2 flex items-center gap-2 text-sm text-bronze" role="alert"><x-icon name="warning" class="size-4 shrink-0" /><span>{{ $message }}</span></p>
    @enderror
</div>
