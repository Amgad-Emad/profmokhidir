{{-- Livewire single-choice field. `options` is value => label. --}}
@props(['name', 'label', 'options' => [], 'placeholder' => null, 'hint' => null, 'required' => false, 'live' => false])
@php
    $id = str_replace(['.', '_'], '-', $name);
    $hintId = $id.'-hint';
    $errorId = $id.'-error';
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="t-label block text-accent-text">{{ $label }}@if ($required) <span aria-hidden="true">*</span>@endif</label>
    @if ($hint)
        <p id="{{ $hintId }}" class="mt-2 text-sm font-light text-ink-muted">{{ $hint }}</p>
    @endif
    <span class="select-wrap"><select id="{{ $id }}" wire:model{{ $live ? '.live' : '.blur' }}="{{ $name }}"
            @if ($required) aria-required="true" @endif
            aria-describedby="{{ trim(($hint ? $hintId : '').' '.($errors->has($name) ? $errorId : '')) }}"
            @error($name) aria-invalid="true" @enderror
            {{ $attributes->except('class')->merge(['class' => 'form-select mt-2 block w-full rounded-card border border-rule-strong bg-surface-field px-4 py-3 text-body text-ink focus:border-focus']) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
        @endforeach
    </select><span class="select-chevron" aria-hidden="true"></span></span>
    @error($name)
        <p id="{{ $errorId }}" class="mt-2 flex items-center gap-2 text-sm text-danger-tone" role="alert"><x-icon name="warning" class="size-4 shrink-0" /><span>{{ $message }}</span></p>
    @enderror
</div>
