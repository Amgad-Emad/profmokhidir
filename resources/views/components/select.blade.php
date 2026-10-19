{{-- Single-choice field in the form style. `options` is value => label. --}}
@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false, 'placeholder' => null, 'hint' => null])
@php
    $selected = (string) old($name, $value ?? '');
    $hintId = $name.'-hint';
@endphp
<div>
    <label for="{{ $name }}" class="t-label block text-bronze">{{ $label }}</label>
    @if ($hint)
        <p id="{{ $hintId }}" class="mt-2 text-sm font-light text-ash">{{ $hint }}</p>
    @endif
    <select id="{{ $name }}" name="{{ $name }}"
            @if ($required) required @endif
            @if ($hint) aria-describedby="{{ $hintId }}" @endif
            {{ $attributes->merge(['class' => 'form-select mt-2 block w-full rounded-card border border-stone bg-ivory px-4 py-3 text-body text-evergreen focus:border-bronze']) }}>
        @if ($placeholder !== null)
            <option value="" @selected($selected === '')>{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="mt-2 text-sm text-bronze" role="alert">{{ $message }}</p>
    @enderror
</div>
