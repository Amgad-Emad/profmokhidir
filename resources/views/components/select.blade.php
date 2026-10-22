{{-- Single-choice field in the form style. `options` is value => label. --}}
@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false, 'placeholder' => null, 'hint' => null])
@php
    $selected = (string) old($name, $value ?? '');
    $hintId = $name.'-hint';
@endphp
<div>
    <label for="{{ $name }}" class="t-label block text-accent-text">{{ $label }}</label>
    @if ($hint)
        <p id="{{ $hintId }}" class="mt-2 text-sm font-light text-ink-muted">{{ $hint }}</p>
    @endif
    <span class="select-wrap mt-2">
    <select id="{{ $name }}" name="{{ $name }}"
            @if ($required) required @endif
            @if ($hint) aria-describedby="{{ $hintId }}" @endif
            {{ $attributes->merge(['class' => 'form-select block w-full rounded-card border border-rule-strong bg-surface-field px-4 py-3 text-body text-ink focus:border-focus']) }}>
        @if ($placeholder !== null)
            <option value="" @selected($selected === '')>{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    <span class="select-chevron" aria-hidden="true"></span>
    </span>
    @error($name)
        <p class="mt-2 text-sm text-danger-tone" role="alert">{{ $message }}</p>
    @enderror
</div>
